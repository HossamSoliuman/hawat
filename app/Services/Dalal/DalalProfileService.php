<?php

namespace App\Services\Dalal;

use App\Models\DalalProfile;
use App\Models\DalalWorker;
use App\Models\DalalWorkerCount;
use App\Models\DalalWorkerType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * إعدادات الدلال: ملفه التجاري (الموقع والدكة والسجل التجاري والضريبي)،
 * ومعلومات الشركة وشعارها الذي يُطبع على الفواتير، وعمالة الدكة أفرادًا
 * (وأعدادها محسوبة منهم). الاسم والبريد والصورة وكلمة المرور في الملف الشخصي
 * المشترك بين الأدوار.
 */
class DalalProfileService
{
    public function update(User $dalal, array $data): DalalProfile
    {
        $profile = DalalProfile::forUser($dalal);
        $profile->update($data);

        return $profile;
    }

    public function storeLogo(User $dalal, UploadedFile $file): DalalProfile
    {
        $profile = DalalProfile::forUser($dalal);

        if ($profile->logo_path) {
            Storage::disk('public')->delete($profile->logo_path);
        }

        $profile->update(['logo_path' => $file->store('dalal-logos', 'public')]);

        return $profile;
    }

    public function removeLogo(User $dalal): DalalProfile
    {
        $profile = DalalProfile::forUser($dalal);

        if ($profile->logo_path) {
            Storage::disk('public')->delete($profile->logo_path);
            $profile->update(['logo_path' => null]);
        }

        return $profile;
    }

    public function createWorker(User $dalal, array $data): DalalWorker
    {
        return DalalWorker::create(['dalal_id' => $dalal->id] + array_filter($data, fn ($v) => $v !== null) + ['status' => 'نشط']);
    }

    /**
     * النموذج كاملًا؛ الحالة الغائبة تبقى كما هي.
     */
    public function updateWorker(DalalWorker $worker, array $data): DalalWorker
    {
        if (array_key_exists('status', $data) && $data['status'] === null) {
            unset($data['status']);
        }

        $worker->update($data);

        return $worker;
    }

    public function setWorkerStatus(DalalWorker $worker, string $status): DalalWorker
    {
        $worker->update(['status' => $status]);

        return $worker;
    }

    public function storeWorkerAvatar(DalalWorker $worker, UploadedFile $file): DalalWorker
    {
        if ($worker->avatar_path) {
            Storage::disk('public')->delete($worker->avatar_path);
        }

        $worker->update(['avatar_path' => $file->store('avatars/dalal-workers', 'public')]);

        return $worker;
    }

    public function removeWorkerAvatar(DalalWorker $worker): DalalWorker
    {
        if ($worker->avatar_path) {
            Storage::disk('public')->delete($worker->avatar_path);
            $worker->update(['avatar_path' => null]);
        }

        return $worker;
    }

    /**
     * أعداد عمالة الدكة حسب النوع والجنسية: من سجلات العمال النشطين متى سجّل
     * الدلال عاملًا واحدًا على الأقل، وإلا من سطور الأعداد القديمة كما هي.
     *
     * @return array{source: 'records'|'counts', rows: Collection<int, array{id: int|null, type: DalalWorkerType|null, nationality: string|null, count: int, notes: string|null}>}
     */
    public function workforce(User $dalal): array
    {
        if (DalalWorker::forDalal($dalal)->exists()) {
            $rows = DalalWorker::forDalal($dalal)->active()->with('type')->get()
                ->groupBy(fn (DalalWorker $w) => $w->dalal_worker_type_id.'|'.$w->nationality)
                ->map(fn (Collection $group) => [
                    'id' => null,
                    'type' => $group->first()->type,
                    'nationality' => $group->first()->nationality,
                    'count' => $group->count(),
                    'notes' => null,
                ])
                ->sortBy(fn ($row) => [$row['type']?->display_order ?? 0, $row['nationality'] ?? ''])
                ->values();

            return ['source' => 'records', 'rows' => $rows];
        }

        $rows = DalalWorkerCount::forDalal($dalal)->with('type')->orderBy('id')->get()
            ->map(fn (DalalWorkerCount $row) => [
                'id' => $row->id,
                'type' => $row->type,
                'nationality' => $row->nationality,
                'count' => (int) $row->count,
                'notes' => $row->notes,
            ]);

        return ['source' => 'counts', 'rows' => $rows];
    }

    /**
     * يستبدل سطور الأعداد القديمة كلها (PUT /dalal/settings/workers، مهجور). بعد
     * تسجيل العمال أفرادًا تُحسب الأعداد من السجلات، فلا يُقبل هذا المسار.
     *
     * @param  array<int, array{dalal_worker_type_id:int, nationality?:string|null, count:int, notes?:string|null}>  $rows
     */
    public function syncWorkerCounts(User $dalal, array $rows): void
    {
        if (DalalWorker::forDalal($dalal)->exists()) {
            throw ValidationException::withMessages(['workers' => 'عمالتك مسجّلة أفرادًا الآن — أضف العمال وعدّلهم من قائمة العمال، والأعداد تُحسب منها.']);
        }

        DB::transaction(function () use ($dalal, $rows) {
            DalalWorkerCount::forDalal($dalal)->delete();

            foreach ($rows as $row) {
                DalalWorkerCount::create($row + ['dalal_id' => $dalal->id]);
            }
        });
    }
}
