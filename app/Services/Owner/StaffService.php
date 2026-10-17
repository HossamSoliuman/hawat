<?php

namespace App\Services\Owner;

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\Fisher;
use App\Models\FisherRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * كباتن المالك وطاقمه — سجلات صيادين في الوزارة (fishers) بلا حساب دخول،
 * فيظهرون في صفحة الميناء ومركز المعلومات، والمالك يدير الرحلة عنهم كلها.
 * الكابتن سجلّ بـ`is_captain` يُسند إلى قارب واحد (boats.captain_id) وإلى
 * رحلاته (trips.captain_id).
 */
class StaffService
{
    public function createCaptain(User $owner, array $data): Fisher
    {
        $captain = DB::transaction(function () use ($owner, $data) {
            $captain = Fisher::create($this->captainAttributes($owner, $data));
            $this->assignBoat($owner, $captain, $captain->boat_id);

            $this->log($owner, 'إضافة كابتن', $captain->name);

            return $captain;
        });

        // القارب قد يحدّث السجلّ من جهته (Boat::booted) — النسخة المعادة أحدث ما كُتب.
        return $captain->refresh()->load('boat', 'port', 'idType', 'fisherRole');
    }

    public function updateCaptain(Fisher $captain, array $data): Fisher
    {
        DB::transaction(function () use ($captain, $data) {
            $captain->update($this->captainAttributes($captain->owner, $data, $captain));

            if (array_key_exists('boat_id', $data)) {
                $this->assignBoat($captain->owner, $captain, $captain->boat_id);
            }

            $this->log($captain->owner, 'تحديث كابتن', $captain->name);
        });

        // القارب قد يحدّث السجلّ من جهته (Boat::booted) — النسخة المعادة أحدث ما كُتب.
        return $captain->refresh()->load('boat', 'port', 'idType', 'fisherRole');
    }

    /**
     * الكابتن غير النشط يبقى على قاربه، وجاهزية القارب تُظهره ناقصًا
     * (captain_inactive) حتى يُفعَّل أو يُستبدل.
     */
    public function setCaptainStatus(Fisher $captain, string $status): Fisher
    {
        $captain->update(['status' => $status]);

        return $captain;
    }

    /**
     * الحذف يرفعه عن قاربه ورحلاته؛ اسمه يبقى على الرحلات (captain_name).
     */
    public function deleteCaptain(Fisher $captain): void
    {
        DB::transaction(function () use ($captain) {
            $this->assignBoat($captain->owner, $captain, null);
            $this->removeAvatar($captain);
            $captain->delete();

            $this->log($captain->owner, 'حذف كابتن', $captain->name);
        });
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
     * صورة الكابتن أو فرد الطاقم في سجلّه.
     */
    public function storeAvatar(Fisher $fisher, UploadedFile $file): Fisher
    {
        if ($fisher->avatar_path) {
            Storage::disk('public')->delete($fisher->avatar_path);
        }

        $fisher->update(['avatar_path' => $file->store($fisher->is_captain ? 'avatars/captains' : 'avatars/crew', 'public')]);

        return $fisher;
    }

    public function removeAvatar(Fisher $fisher): Fisher
    {
        if ($fisher->avatar_path) {
            Storage::disk('public')->delete($fisher->avatar_path);
            $fisher->update(['avatar_path' => null]);
        }

        return $fisher;
    }

    /**
     * يُسنَد الكابتن إلى القارب المختار أو يُرفع عن قاربه. القارب يُبقي
     * الطرفين متّسقين (Boat::booted): يُرفع الكابتن عن قاربه الآخر، وكابتن
     * القارب السابق ينزل عنه.
     */
    private function assignBoat(User $owner, Fisher $captain, ?int $boatId): void
    {
        if ($boatId) {
            Boat::forOwner($owner)->whereKey($boatId)->first()?->update(['captain_id' => $captain->id]);

            return;
        }

        Boat::forOwner($owner)->where('captain_id', $captain->id)->get()->each->update(['captain_id' => null]);
    }

    private function captainAttributes(User $owner, array $data, ?Fisher $current = null): array
    {
        $boat = ! empty($data['boat_id']) ? Boat::forOwner($owner)->find($data['boat_id']) : null;

        return [
            'is_captain' => true,
            'fisher_role_id' => FisherRole::named(Fisher::CAPTAIN_ROLE)->id,
            'role' => Fisher::CAPTAIN_ROLE,
            'status' => $data['status'] ?? $current?->status ?? Fisher::ACTIVE,
        ] + $this->personAttributes($owner, $data, $boat, $current);
    }

    private function crewAttributes(User $owner, array $data): array
    {
        $boat = ! empty($data['boat_id']) ? Boat::forOwner($owner)->find($data['boat_id']) : null;

        return [
            'fisher_role_id' => $data['fisher_role_id'] ?? null,
            'status' => $data['status'] ?? Fisher::ACTIVE,
        ] + $this->personAttributes($owner, $data, $boat);
    }

    private function personAttributes(User $owner, array $data, ?Boat $boat, ?Fisher $current = null): array
    {
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
            'boat_id' => $boat?->id,
            'port_id' => $boat?->port_id ?? $data['port_id'] ?? $current?->port_id,
            'license_number' => $data['license_number'] ?? null,
            'license_date' => $data['license_date'] ?? null,
            'license_expiry' => $data['license_expiry'] ?? null,
            'experience_years' => $data['experience_years'] ?? 0,
        ];
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
