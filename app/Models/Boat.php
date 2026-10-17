<?php

namespace App\Models;

use App\Models\Concerns\HasVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * القارب سجلّ الوزارة نفسه: يضيفه المالك من بوابته فيظهر في صفحة الميناء
 * ومركز المعلومات وعدّادات لوحة الحكومة. الأعمدة النصية القديمة (owner,
 * captain, boat_type) تُملأ من العلاقات عند الحفظ حتى تبقى صفحات الوزارة صادقة.
 */
class Boat extends BaseModel
{
    use HasFactory, HasVersion;

    public const STATUSES = ['نشط', 'غير نشط', 'صيانة', 'في البحر'];

    /** تضبطه الرحلات وحدها: يبدأ الكابتن الرحلة فيصير القارب في البحر. */
    public const AT_SEA = 'في البحر';

    protected $casts = [
        'capacity' => 'float',
        'license_expiry' => 'date',
        'license_date' => 'date',
        'next_inspection_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Boat $boat) {
            if ($boat->isDirty('owner_id') && $boat->owner_id) {
                $boat->owner = User::find($boat->owner_id)?->name ?? $boat->owner;
            }
            if ($boat->isDirty('captain_id')) {
                $boat->captain = $boat->captain_id ? User::find($boat->captain_id)?->name : null;
            }
            if ($boat->isDirty('boat_type_id') && $boat->boat_type_id) {
                $boat->boat_type = BoatType::find($boat->boat_type_id)?->name ?? $boat->boat_type;
            }
        });
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function captainUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BoatCategory::class, 'boat_category_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(BoatType::class, 'boat_type_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function fishers(): HasMany
    {
        return $this->hasMany(Fisher::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(BoatMaintenance::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(BoatInspection::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(FleetDocument::class, 'documentable');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    /**
     * عدد طاقم قارب المالك = أفراد الطاقم النشطون المسجّلون عليه (بلا الكابتن).
     * قوارب الوزارة بلا مالك تبقى أرقامها المستوردة.
     */
    public function refreshCrewCount(): void
    {
        if ($this->owner_id === null) {
            return;
        }

        $count = $this->fishers()->whereNull('user_id')->where('status', 'نشط')->count();

        if ((int) $this->crew_count !== $count) {
            $this->update(['crew_count' => $count]);
        }
    }

    public static function refreshCrewCountFor(int|string|null ...$ids): void
    {
        static::query()->whereKey(array_unique(array_filter($ids)))->get()->each->refreshCrewCount();
    }

    /**
     * اكتمال إعداد القارب للرحلات. "ناقص" يمنع الاكتمال: قارب معطّل أو في
     * الصيانة، أو بلا كابتن، أو كابتنه معطّل، أو بلا فرد طاقم نشط. "تنبيه" لا
     * يمنعه: كابتن لم يفعّل حسابه بعد (المالك يبدأ الرحلة عنه)، أو رخصة
     * منتهية، أو طاقم أكثر من سعة القارب. يقرأ captainUser — حمّله مع القائمة.
     *
     * @return array{ready: bool, missing: list<array{key: string, label: string}>, warnings: list<array{key: string, label: string}>}
     */
    public function setup(): array
    {
        $missing = [];
        $warnings = [];
        $captain = $this->captainUser;

        if (in_array($this->status, ['غير نشط', 'صيانة'], true)) {
            $missing[] = ['key' => 'status', 'label' => $this->status === 'صيانة' ? 'القارب في الصيانة' : 'القارب غير نشط'];
        }

        if (! $captain) {
            $missing[] = ['key' => 'captain', 'label' => 'لم يُسند إليه كابتن'];
        } elseif (! $captain->active) {
            $missing[] = ['key' => 'captain_inactive', 'label' => 'حساب الكابتن معطّل'];
        } elseif ($captain->invitation_status !== User::INVITATION_ACTIVATED) {
            $warnings[] = ['key' => 'captain_not_activated', 'label' => 'الكابتن لم يفعّل حسابه بعد'];
        }

        if ((int) $this->crew_count < 1) {
            $missing[] = ['key' => 'crew', 'label' => 'لا فرد طاقم نشط على القارب'];
        } elseif ((int) $this->crew_capacity > 0 && (int) $this->crew_count > (int) $this->crew_capacity) {
            $warnings[] = ['key' => 'crew_over_capacity', 'label' => 'الطاقم أكثر من سعة القارب'];
        }

        if ($this->license_expiry && $this->license_expiry->lt(today())) {
            $warnings[] = ['key' => 'license_expired', 'label' => 'رخصة القارب منتهية'];
        }

        return ['ready' => $missing === [], 'missing' => $missing, 'warnings' => $warnings];
    }
}
