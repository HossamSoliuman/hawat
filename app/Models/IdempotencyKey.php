<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * طلب POST حمل ترويسة Idempotency-Key: بصمته (الفعل والمسار والمحتوى) وردّه
 * بعد التنفيذ. `status_code = null` يعني أن الطلب الأول ما زال يُنفَّذ.
 * انظر App\Http\Middleware\Idempotent.
 */
class IdempotencyKey extends BaseModel
{
    use MassPrunable;

    /** مدة حفظ الردّ لإعادة الطلب نفسه. */
    public const TTL_HOURS = 24;

    /** طلب أول "قيد التنفيذ" أقدم من هذا تعطّل في منتصفه — يُستعاد مفتاحه. */
    public const STALE_SECONDS = 60;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status_code !== null;
    }

    public function isStale(): bool
    {
        return ! $this->isCompleted() && $this->created_at->lt(now()->subSeconds(self::STALE_SECONDS));
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subHours(self::TTL_HOURS));
    }
}
