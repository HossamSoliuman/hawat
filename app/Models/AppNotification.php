<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * إشعار لحساب تطبيق (مالك، عدّاد، دلال…) — يُقرأ في شاشة الإشعارات في الويب
 * والتطبيق، ويُدفع إلى الجوال عند إنشائه. اسمه app_notifications حتى لا
 * يلتبس بجدول notifications الذي يحجزه Laravel لقناة قاعدة البيانات.
 */
class AppNotification extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'pushed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(NotificationType::class, 'notification_type_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markRead(): void
    {
        if (! $this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }
}
