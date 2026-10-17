<?php

namespace App\Models;

use App\Models\Concerns\HasVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

/**
 * الصياد سجلّ الوزارة (الهوية والرخصة والميناء). الكابتن له فوقه حساب دخول
 * (`user_id`) لأنه يعمل من التطبيق؛ الطاقم سجلّ فقط. `role` النصي يبقى
 * لصفحات الوزارة ويُملأ من `fisher_role_id`.
 */
class Fisher extends BaseModel
{
    use HasFactory, HasVersion;

    public const CAPTAIN_ROLE = 'قبطان';

    protected $casts = [
        'license_date' => 'date',
        'license_expiry' => 'date',
        'iqama_issue_date' => 'date',
        'iqama_expiry_date' => 'date',
        'fixed_salary' => 'float',
        'profit_shares' => 'float',
        'custom_share_percent' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (Fisher $fisher) {
            if ($fisher->isDirty('fisher_role_id') && $fisher->fisher_role_id) {
                $fisher->role = FisherRole::find($fisher->fisher_role_id)?->name ?? $fisher->role;
            }
        });

        // عدد طاقم القارب مشتق من سجلات أفراده: يُعاد حسابه للقارب الذي دخله
        // الفرد والقارب الذي تركه.
        static::saved(function (Fisher $fisher) {
            if ($fisher->wasRecentlyCreated || $fisher->wasChanged(['boat_id', 'status', 'user_id'])) {
                Boat::refreshCrewCountFor($fisher->boat_id, $fisher->getOriginal('boat_id'));
            }
        });

        static::deleted(fn (Fisher $fisher) => Boat::refreshCrewCountFor($fisher->boat_id));
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fisherRole(): BelongsTo
    {
        return $this->belongsTo(FisherRole::class);
    }

    public function idType(): BelongsTo
    {
        return $this->belongsTo(IdType::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(FleetDocument::class, 'documentable');
    }

    public function payType(): BelongsTo
    {
        return $this->belongsTo(PayType::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(CrewAdvance::class);
    }

    public function payrollLines(): HasMany
    {
        return $this->hasMany(PayrollLine::class);
    }

    public function getIsCaptainAttribute(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * صورة الفرد: صورة الطاقم في سجلّه، وصورة الكابتن في حسابه.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $path = $this->avatar_path ?? ($this->user_id ? $this->user?->avatar_path : null);

        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    public function scopeCaptains(Builder $query): Builder
    {
        return $query->whereNotNull('user_id');
    }

    public function scopeCrew(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }
}
