<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * عامل في دكة الدلال، سجلًّا فرديًّا: الاسم والجوال والجنسية والهوية والنوع
 * والحالة والصورة. لا يُحذف — يُعطَّل (status = غير نشط) فيبقى في السجل.
 */
class DalalWorker extends BaseModel
{
    use HasFactory;

    public const STATUSES = ['نشط', 'غير نشط'];

    public function dalal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dalal_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DalalWorkerType::class, 'dalal_worker_type_id');
    }

    public function idType(): BelongsTo
    {
        return $this->belongsTo(IdType::class);
    }

    public function scopeForDalal(Builder $query, User $dalal): Builder
    {
        return $query->where('dalal_id', $dalal->id);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'نشط');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null;
    }
}
