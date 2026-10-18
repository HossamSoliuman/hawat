<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * الرحلة تحمل دورتين: `status` هي الدورة التشغيلية/الإحصائية بمفردات
 * الوزارة (تقرؤها صفحات الإحصاء كما هي)، و`sale_status` الدورة التجارية
 * للمالك — تبدأ حين يكتمل العد وتنتهي حين ينفد مصيد الرحلة بيعًا أو إرسالًا.
 */
class Trip extends BaseModel
{
    use HasFactory;

    public const SCHEDULED = 'مجدولة';

    public const AT_SEA = 'في البحر';

    public const RETURNED = 'عادت للميناء';

    public const AWAITING_COUNT = 'بانتظار الإحصاء';

    public const COUNTING = 'تحت الإحصاء';

    public const AWAITING_APPROVAL = 'بانتظار الاعتماد';

    public const APPROVED = 'معتمدة';

    public const CANCELLED = 'ملغاة';

    public const STATUSES = [
        self::SCHEDULED, self::AT_SEA, self::RETURNED, self::AWAITING_COUNT,
        self::COUNTING, self::AWAITING_APPROVAL, self::APPROVED, self::CANCELLED,
    ];

    /**
     * ما يعني العدّاد من دورة الرحلة: منذ عودتها بمصيدها إلى اعتماد الوزارة.
     */
    public const COUNTER_STATUSES = [
        self::RETURNED, self::AWAITING_COUNT, self::COUNTING, self::AWAITING_APPROVAL, self::APPROVED,
    ];

    /** الرحلة لم يُعدّ مصيدها بعد — لا يمكن بيعها. */
    public const SALE_NOT_STARTED = 'لم يبدأ';

    /** اكتمل العد وبقي من مصيدها ما يُباع. */
    public const SALE_OPEN = 'قيد البيع';

    /** نفد مصيد الرحلة بيعًا أو إرسالًا للدلال. */
    public const SALE_DONE = 'مباعة';

    protected $casts = [
        'departure_time' => 'datetime',
        'return_time' => 'datetime',
        'started_at' => 'datetime',
        'catch_submitted_at' => 'datetime',
        'received_at' => 'datetime',
        'counted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function departurePort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'departure_port_id');
    }

    public function returnPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'return_port_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * كابتن الرحلة: سجلّ صياد عند المالك، لا حساب دخول.
     */
    public function captain(): BelongsTo
    {
        return $this->belongsTo(Fisher::class, 'captain_id');
    }

    public function counter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counter_id');
    }

    public function tripType(): BelongsTo
    {
        return $this->belongsTo(TripType::class);
    }

    public function catchRecords(): HasMany
    {
        return $this->hasMany(CatchRecord::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function scopeForOwner(Builder $query, User $owner): Builder
    {
        return $query->where('owner_id', $owner->id);
    }

    /**
     * رئيسة المالك "رحلات بانتظار الانطلاق": أُنشئت ولم يبدأها بعد.
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', self::SCHEDULED);
    }

    /**
     * ما يخصّ العدّاد من رحلات ميناء واحد: منذ عودتها إلى ما بعد عدّها.
     * الرحلة المجدولة أو التي في البحر ليست من شأنه، ورحلة ميناء آخر لا
     * يراها ولا يعدّها (404) — إلا رحلة عدّها هو ثم نُقل عن الميناء.
     *
     * ميناء الرحلة ميناء عودتها، وإن لم يُحدَّد فميناء مغادرتها.
     *
     * @param  array<int, int>  $portIds
     */
    public function scopeAtPorts(Builder $query, array $portIds): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereIn('return_port_id', $portIds)
            ->orWhere(fn (Builder $w) => $w->whereNull('return_port_id')->whereIn('departure_port_id', $portIds)));
    }

    public function scopeForCounter(Builder $query, User $counter): Builder
    {
        return $query
            ->whereIn('status', self::COUNTER_STATUSES)
            ->where(fn (Builder $q) => $q->atPorts($counter->counterPortIds())->orWhere('counter_id', $counter->id));
    }

    /**
     * شاشة العدّاد "رحلات بحاجة لموافقتك": عادت بمصيدها ولم تُستلم بعد.
     */
    public function scopeAwaitingCounter(Builder $query): Builder
    {
        return $query->whereIn('status', [self::RETURNED, self::AWAITING_COUNT]);
    }

    /**
     * شاشة العدّاد "الرحلات النشطة": استُلمت وجارٍ عدّها.
     */
    public function scopeUnderCount(Builder $query): Builder
    {
        return $query->where('status', self::COUNTING);
    }

    /**
     * الرحلات التي يتابعها المالك في شاشة "الرحلات النشطة": من الانطلاق حتى
     * ما بعد العد ما دام مصيدها لم يُبع.
     */
    public function scopeActiveForOwner(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereIn('status', [self::AT_SEA, self::RETURNED, self::AWAITING_COUNT, self::COUNTING])
            ->orWhere('sale_status', self::SALE_OPEN));
    }

    /**
     * مراحل رحلات المالك كما تبويبات التطبيق — لا تتداخل، فالرحلة في واحدة منها:
     * مجدولة، ثم جارية (في البحر وعند العدّاد)، ثم للبيع، ثم مكتملة (بيع
     * مصيدها كله، أو عُدّت بلا مصيد يُباع)، أو ملغاة.
     */
    public const STAGES = ['scheduled', 'in_progress', 'for_sale', 'done', 'cancelled'];

    public function scopeStage(Builder $query, string $stage): Builder
    {
        return match ($stage) {
            'scheduled' => $query->where('status', self::SCHEDULED),
            'in_progress' => $query->whereIn('status', [self::AT_SEA, self::RETURNED, self::AWAITING_COUNT, self::COUNTING]),
            'for_sale' => $query->where('sale_status', self::SALE_OPEN),
            'done' => $query->where(fn (Builder $q) => $q
                ->where('sale_status', self::SALE_DONE)
                ->orWhere(fn (Builder $w) => $w->whereIn('status', [self::AWAITING_APPROVAL, self::APPROVED])->where('sale_status', self::SALE_NOT_STARTED))),
            'cancelled' => $query->where('status', self::CANCELLED),
            default => $query,
        };
    }

    public function isCancelled(): bool
    {
        return $this->status === self::CANCELLED;
    }

    public function isCounted(): bool
    {
        return in_array($this->status, [self::AWAITING_APPROVAL, self::APPROVED], true);
    }

    /**
     * ما يستطيعه المالك على الرحلة الآن — تقرؤه الشاشات وواجهة التطبيق.
     */
    public function canStart(): bool
    {
        return $this->status === self::SCHEDULED;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [self::SCHEDULED, self::AT_SEA], true);
    }

    public function canSubmitCatch(): bool
    {
        return $this->status === self::AT_SEA;
    }

    /**
     * ما يستطيعه العدّاد على الرحلة الآن — تقرؤه الشاشات وواجهة التطبيق.
     */
    public function canReceive(): bool
    {
        return in_array($this->status, [self::RETURNED, self::AWAITING_COUNT], true);
    }

    public function canCount(): bool
    {
        return in_array($this->status, [self::RETURNED, self::AWAITING_COUNT, self::COUNTING, self::AWAITING_APPROVAL], true);
    }

    public function canSell(): bool
    {
        return $this->sale_status === self::SALE_OPEN;
    }

    /**
     * شريط التقدّم في تطبيق المالك: أربع خطوات فوق حالات الوزارة.
     * 0 = لم تبدأ، 1 = بدأت، 2 = بانتظار العدّاد، 3 = جارية العد، 4 = اكتمل العد.
     */
    public function getProgressStepAttribute(): int
    {
        return match ($this->status) {
            self::AT_SEA => 1,
            self::RETURNED, self::AWAITING_COUNT => 2,
            self::COUNTING => 3,
            self::AWAITING_APPROVAL, self::APPROVED => 4,
            default => 0,
        };
    }

    /**
     * تسمية الحالة كما يراها المالك في التطبيق.
     */
    public function getAppStatusAttribute(): string
    {
        return match ($this->status) {
            self::SCHEDULED => 'بانتظار الانطلاق',
            self::AT_SEA => 'بدأت الرحلة',
            self::RETURNED, self::AWAITING_COUNT => 'بانتظار العدّاد',
            self::COUNTING => 'جارية العد',
            self::AWAITING_APPROVAL, self::APPROVED => match ($this->sale_status) {
                self::SALE_OPEN => 'اكتمل العد وجاهزة للبيع',
                self::SALE_DONE => 'مكتملة',
                // رحلة عُدّت قبل أن يكون لها مالك في النظام — لا مخزون يُباع منها.
                default => 'اكتمل العد',
            },
            self::CANCELLED => 'ملغاة',
            default => $this->status,
        };
    }

    /**
     * الرقم التالي بصيغة TR-YYYY-NNNN — تتبع تسلسل السنة الحالية.
     */
    public static function nextNumber(): string
    {
        $prefix = 'TR-'.now()->year.'-';
        $last = static::where('trip_number', 'like', $prefix.'%')->orderByDesc('trip_number')->value('trip_number');
        $seq = $last ? (int) substr($last, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
