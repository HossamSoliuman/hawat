<?php

namespace App\Services\Owner;

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\Fisher;
use App\Models\FisherRole;
use App\Models\Role;
use App\Models\User;
use App\Services\Account\InvitationService;
use App\Services\Account\ProfileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * كباتن المالك وطاقمه. الكابتن حساب دخول (users بدور captain يتبع المالك)
 * فوق سجلّ صياد في الوزارة (fishers) — فيدخل التطبيق ويظهر في صفحة الميناء
 * ومركز المعلومات معًا. الطاقم سجلّ صياد فقط.
 *
 * الكابتن يُنشأ بكلمة مرور (مفعّلًا) أو بدعوة إلى جواله يفعّلها بنفسه
 * (InvitationService). نسخة الكابتن للتعديل المتزامن = نسخة سجلّ صياده.
 */
class StaffService
{
    public function __construct(
        private readonly InvitationService $invitations,
        private readonly ProfileService $profiles,
    ) {}

    public function createCaptain(User $owner, array $data): User
    {
        $invite = empty($data['password']);

        $captain = DB::transaction(function () use ($owner, $data, $invite) {
            $captain = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'password' => $invite ? InvitationService::placeholderPassword() : $data['password'],
                'role_id' => Role::key(Role::CAPTAIN)->id,
                'owner_id' => $owner->id,
                'active' => $data['active'] ?? true,
            ]);
            $captain->forceFill(['activated_at' => $invite ? null : now()])->save();

            $this->syncFisher($owner, $captain, $data);
            $this->assignBoat($owner, $captain, $data['boat_id'] ?? null);

            $this->log($owner, $invite ? 'دعوة كابتن' : 'إنشاء كابتن', $captain->name);

            return $captain;
        });

        // الرسالة بعد أن يثبت الحساب، فلا تصل دعوة لحساب تراجعت معاملته.
        if ($invite) {
            $this->invitations->send($captain, $owner);
        }

        return $captain->load('fisher.boat', 'fisher.port');
    }

    public function updateCaptain(User $captain, array $data): User
    {
        $phoneChanged = false;

        DB::transaction(function () use ($captain, $data, &$phoneChanged) {
            $attributes = [
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'active' => $data['active'] ?? $captain->active,
            ];
            // كلمة المرور تُغيَّر فقط حين تُرسل؛ الفارغة تعني "كما هي". ومن
            // وضعها المالك لكابتن مدعوّ فقد فعّل حسابه بها.
            if (! empty($data['password'])) {
                $attributes['password'] = $data['password'];
            }
            $captain->update($attributes);
            $phoneChanged = $captain->wasChanged('phone');

            if (! empty($data['password']) && $captain->isPendingInvitation()) {
                $captain->forceFill(['activated_at' => now()])->save();
            }

            if (! $captain->active) {
                $captain->tokens()->delete();
            }

            $fisher = $this->syncFisher($captain->owner, $captain, $data);

            // تعديل يمسّ الحساب وحده (الاسم يُنسخ إلى السجلّ، والجوال والبريد
            // والتفعيل لا) يرفع نسخة السجلّ أيضًا، فالنسخة تمثّل الكابتن كله.
            if ($captain->wasChanged() && ! $fisher->wasChanged()) {
                $this->bumpVersion($fisher);
            }

            if (array_key_exists('boat_id', $data)) {
                $this->assignBoat($captain->owner, $captain, $data['boat_id']);
            }

            $this->log($captain->owner, 'تحديث كابتن', $captain->name);
        });

        // كابتن مدعوّ تغيّر جواله: الدعوة تذهب إلى الجوال الجديد.
        if ($phoneChanged && $captain->isPendingInvitation()) {
            $this->invitations->send($captain, $captain->owner);
        }

        return $captain->load('fisher.boat', 'fisher.port');
    }

    public function setCaptainActive(User $captain, bool $active): User
    {
        DB::transaction(function () use ($captain, $active) {
            $captain->update(['active' => $active]);

            $fisher = $captain->fisher;
            $fisher?->update(['status' => $active ? 'نشط' : 'غير نشط']);
            if ($fisher && ! $fisher->wasChanged()) {
                $this->bumpVersion($fisher);
            }

            if (! $active) {
                $captain->tokens()->delete();
            }
        });

        return $captain;
    }

    /**
     * إعادة إرسال الدعوة برمز جديد يبطل ما قبله، وصلاحية جديدة من الآن.
     */
    public function resendInvitation(User $captain): User
    {
        if (! $captain->isPendingInvitation()) {
            throw ValidationException::withMessages(['invitation' => 'فعّل الكابتن حسابه — لا دعوة لإعادة إرسالها.']);
        }

        if (! $captain->active) {
            throw ValidationException::withMessages(['invitation' => 'حساب الكابتن معطّل — فعّله أولًا.']);
        }

        $this->invitations->send($captain, $captain->owner);
        $this->log($captain->owner, 'إعادة دعوة كابتن', $captain->name);

        return $captain->load('fisher.boat', 'fisher.port');
    }

    public function createCrew(User $owner, array $data): Fisher
    {
        $fisher = Fisher::create($this->crewAttributes($owner, $data));

        $this->log($owner, 'إضافة طاقم', $fisher->name);

        return $fisher->load('boat', 'port', 'fisherRole');
    }

    public function updateCrew(Fisher $fisher, array $data): Fisher
    {
        $fisher->update($this->crewAttributes($fisher->owner, $data));

        $this->log($fisher->owner, 'تحديث طاقم', $fisher->name);

        return $fisher->load('boat', 'port', 'fisherRole');
    }

    /**
     * صورة الكابتن في حسابه — هي نفسها التي يراها ويغيّرها من تطبيقه.
     */
    public function storeCaptainAvatar(User $captain, UploadedFile $file): User
    {
        return $this->profiles->storeAvatar($captain, $file);
    }

    public function removeCaptainAvatar(User $captain): User
    {
        return $this->profiles->removeAvatar($captain);
    }

    public function storeCrewAvatar(Fisher $fisher, UploadedFile $file): Fisher
    {
        if ($fisher->avatar_path) {
            Storage::disk('public')->delete($fisher->avatar_path);
        }

        $fisher->update(['avatar_path' => $file->store('avatars/crew', 'public')]);

        return $fisher;
    }

    public function removeCrewAvatar(Fisher $fisher): Fisher
    {
        if ($fisher->avatar_path) {
            Storage::disk('public')->delete($fisher->avatar_path);
            $fisher->update(['avatar_path' => null]);
        }

        return $fisher;
    }

    /**
     * سجلّ الصياد للكابتن: يُنشأ مع الحساب ويُحدَّث معه.
     */
    private function syncFisher(User $owner, User $captain, array $data): Fisher
    {
        $boat = ! empty($data['boat_id']) ? Boat::forOwner($owner)->find($data['boat_id']) : null;

        return Fisher::updateOrCreate(['user_id' => $captain->id], [
            'owner_id' => $owner->id,
            'name' => $captain->name,
            'phone' => $captain->phone,
            'email' => $captain->email,
            'national_id' => $data['national_id'],
            'id_type_id' => $data['id_type_id'] ?? null,
            'iqama_issue_date' => $data['iqama_issue_date'] ?? null,
            'iqama_expiry_date' => $data['iqama_expiry_date'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'fisher_role_id' => FisherRole::named(Fisher::CAPTAIN_ROLE)->id,
            'role' => Fisher::CAPTAIN_ROLE,
            'boat_id' => $boat?->id,
            'port_id' => $boat?->port_id ?? $data['port_id'] ?? $captain->fisher?->port_id,
            'license_number' => $data['license_number'] ?? null,
            'license_date' => $data['license_date'] ?? null,
            'license_expiry' => $data['license_expiry'] ?? null,
            'experience_years' => $data['experience_years'] ?? 0,
            'status' => ($data['active'] ?? $captain->active) ? 'نشط' : 'غير نشط',
        ]);
    }

    /**
     * الكابتن على قارب واحد: يُسنَد إلى المختار ويُرفع عن غيره.
     */
    private function assignBoat(User $owner, User $captain, ?int $boatId): void
    {
        Boat::forOwner($owner)->where('captain_id', $captain->id)->whereKeyNot($boatId)->get()
            ->each(fn (Boat $boat) => $boat->update(['captain_id' => null]));

        if ($boatId) {
            Boat::forOwner($owner)->whereKey($boatId)->first()?->update(['captain_id' => $captain->id]);
        }
    }

    private function crewAttributes(User $owner, array $data): array
    {
        $boat = ! empty($data['boat_id']) ? Boat::forOwner($owner)->find($data['boat_id']) : null;

        return [
            'owner_id' => $owner->id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'national_id' => $data['national_id'],
            'id_type_id' => $data['id_type_id'] ?? null,
            'iqama_issue_date' => $data['iqama_issue_date'] ?? null,
            'iqama_expiry_date' => $data['iqama_expiry_date'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'fisher_role_id' => $data['fisher_role_id'] ?? null,
            'boat_id' => $boat?->id,
            'port_id' => $boat?->port_id ?? $data['port_id'],
            'license_number' => $data['license_number'] ?? null,
            'license_date' => $data['license_date'] ?? null,
            'license_expiry' => $data['license_expiry'] ?? null,
            'experience_years' => $data['experience_years'] ?? 0,
            'status' => $data['status'] ?? 'نشط',
        ];
    }

    private function bumpVersion(Fisher $fisher): void
    {
        $fisher->forceFill(['version' => $fisher->version + 1])->saveQuietly();
    }

    private function log(User $owner, string $action, string $label): void
    {
        AuditLog::create([
            'user_email' => $owner->email ?? $owner->phone,
            'role' => 'owner',
            'action' => $action,
            'entity' => 'Fisher',
            'record_label' => $label,
            'details' => "{$action}: {$label}",
            'ip' => request()?->ip(),
        ]);
    }
}
