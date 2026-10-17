<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سطر من أعداد عمالة الدكة القديمة: النوع والجنسية والعدد. بقي من قبل تسجيل
 * العمال أفرادًا (DalalWorker)، وأعداد الدلال تُقرأ منه حتى يسجّل أول عامل.
 */
class DalalWorkerCount extends BaseModel
{
    use HasFactory;

    public function dalal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dalal_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DalalWorkerType::class, 'dalal_worker_type_id');
    }

    public function scopeForDalal(Builder $query, User $dalal): Builder
    {
        return $query->where('dalal_id', $dalal->id);
    }
}
