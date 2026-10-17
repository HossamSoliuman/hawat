<?php

namespace App\Services\Notifications;

use App\Models\AppNotification;
use App\Models\Consignment;
use App\Models\CounterApplication;
use App\Models\DalalInvoiceReview;
use App\Models\DalalPartnership;
use App\Models\DalalPayout;
use App\Models\NotificationType;
use App\Models\Port;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StatisticsOfficer;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Collection;
use Throwable;

/**
 * يكتب إشعار التطبيق ويدفعه إلى جوال صاحبه.
 *
 * المصدر الوحيد لإشعارات الرحلة: TripService يستدعيه عند كل انتقال في
 * الدورة. الكابتن سجلّ بلا حساب فلا يُبلَّغ؛ المالك يُبلَّغ بما يفعله غيره في
 * رحلته (العد خصوصًا)، والعدّاد بما عاد إلى مينائه. الثوابت هنا أسماء صفوف
 * notification_types المبذورة (كما StockLedger مع أنواع الحركات).
 */
class Notifier
{
    public const TRIP_STARTED = 'الرحلة قيد التنفيذ';

    public const TRIP_CANCELLED = 'تم إلغاء الرحلة';

    public const CATCH_SUBMITTED = 'تم إرسال المخرجات';

    public const COUNT_COMPLETED = 'اكتمل العد';

    public const COUNT_REQUESTED = 'رحلة بانتظار العد';

    public const STOCK_RECEIVED = 'مصيد جديد في مخزونك';

    public const PARTNERSHIP_REQUESTED = 'طلب تعامل من مالك';

    public const PARTNERSHIP_ACCEPTED = 'تم قبول طلب التعامل';

    public const PARTNERSHIP_REJECTED = 'تم رفض طلب التعامل';

    public const DALAL_SOLD = 'بيع من مصيدك';

    public const PAYOUT_RECEIVED = 'دفعة من الدلال';

    public const INVOICE_ACCEPTED = 'قبل المالك فاتورتك';

    public const INVOICE_REJECTED = 'رفض المالك فاتورتك';

    public const INVOICE_REPLIED = 'رد الدلال على فاتورة مرفوضة';

    public const RECEIPT_RECORDED = 'سجّل المالك استلام دفعة';

    public const COUNTER_APPLIED = 'طلب توظيف عدّاد';

    public const COUNTER_TRANSFERRED = 'نُقلت إلى ميناء آخر';

    public function __construct(private readonly PushSender $push) {}

    /**
     * يحفظ الإشعار ثم يدفعه إن كان للحساب رمز جهاز. فشل الدفع لا يُفشل
     * العملية التي أنتجت الإشعار — يبقى في الجدول ويُقرأ من الشاشة.
     */
    public function notify(?User $to, string $type, string $title, string $body, ?Trip $trip = null, array $data = []): ?AppNotification
    {
        if ($to === null) {
            return null;
        }

        $notification = AppNotification::create([
            'user_id' => $to->id,
            'notification_type_id' => NotificationType::named($type)->id,
            'trip_id' => $trip?->id,
            'title' => $title,
            'body' => $body,
            'data' => array_filter([
                'type' => $type,
                'trip_id' => $trip?->id,
                'trip_number' => $trip?->trip_number,
                'status' => $trip?->status,
            ] + $data, fn ($v) => $v !== null),
        ]);

        if ($to->fcm_token) {
            try {
                if ($this->push->send($to->fcm_token, $notification)) {
                    $notification->update(['pushed_at' => now()]);
                }
            } catch (Throwable) {
                // الدفع تحسين لا شرط: الإشعار محفوظ ويُعرض في الشاشة.
            }
        }

        return $notification;
    }

    /*
     * إشعارات دورة الرحلة — النصوص كما تظهر في شاشة الإشعارات في التطبيق.
     */

    /**
     * من فعل لا يُبلَّغ بفعله: المالك يبدأ رحلته ويلغيها ويرسل مخرجاتها
     * فلا إشعار له، وإن فعلها غيره (موظف الوزارة مثلًا) بُلِّغ المالك.
     */
    public function tripStarted(Trip $trip, ?User $by): void
    {
        $this->notify($this->ownerUnlessActor($trip, $by), self::TRIP_STARTED, 'الرحلة قيد التنفيذ',
            "انطلقت الرحلة {$trip->trip_number} — القارب ".($trip->boat?->name ?? '').' في البحر الآن.', $trip);
    }

    public function tripCancelled(Trip $trip, ?User $by): void
    {
        $this->notify($this->ownerUnlessActor($trip, $by), self::TRIP_CANCELLED, 'تم إلغاء الرحلة',
            "أُلغيت الرحلة {$trip->trip_number}: {$trip->cancel_reason}", $trip);
    }

    public function catchSubmitted(Trip $trip, ?User $by): void
    {
        $this->notify($this->ownerUnlessActor($trip, $by), self::CATCH_SUBMITTED, 'تم إرسال المخرجات',
            "أُرسلت مخرجات الرحلة {$trip->trip_number}: ".number_format((float) $trip->captain_input_kg, 1).' كجم — بانتظار العدّاد.', $trip);
    }

    /**
     * الرحلة عادت بمصيدها: عدّادو ميناء العودة يُبلَّغون بها لتظهر في
     * "رحلات بحاجة لموافقتك" — أوّلُهم استلامًا هو من يعدّها.
     */
    public function countRequested(Trip $trip): void
    {
        $kg = number_format((float) $trip->captain_input_kg, 1);
        $port = $trip->returnPort?->name ?? $trip->departurePort?->name ?? 'الميناء';

        foreach ($this->countersOf($trip) as $counter) {
            $this->notify($counter, self::COUNT_REQUESTED, 'رحلة بانتظار العد',
                "عادت الرحلة {$trip->trip_number} إلى {$port} بمصيد معلن {$kg} كجم — استلمها لبدء العد.", $trip);
        }
    }

    /**
     * اكتمال العد يفتح البيع عند المالك.
     */
    public function countCompleted(Trip $trip): void
    {
        $kg = number_format((float) $trip->actual_weight_kg, 1);

        $this->notify($trip->owner, self::COUNT_COMPLETED, 'اكتمل العد',
            "عُدّ مصيد الرحلة {$trip->trip_number} ({$kg} كجم) وصار جاهزًا للبيع.", $trip);
    }

    /*
     * إشعارات الدلال والمالك حول المخزون المرسل وبيعه وتسويته. `data.target`
     * يحدّد الشاشة التي يفتحها الإشعار حين لا رحلة له في بوابة الدور.
     */

    public function stockReceived(Consignment $consignment): void
    {
        $this->notify($consignment->dalal, self::STOCK_RECEIVED, 'مصيد جديد في مخزونك',
            "أرسل {$consignment->owner?->name} ".number_format((float) $consignment->total_kg, 1)." كجم من الرحلة {$consignment->trip?->trip_number} ({$consignment->consignment_number}).",
            $consignment->trip, ['target' => 'stock', 'consignment_id' => $consignment->id]);
    }

    public function partnershipRequested(DalalPartnership $partnership): void
    {
        $this->notify($partnership->dalal, self::PARTNERSHIP_REQUESTED, 'طلب تعامل من مالك',
            "{$partnership->owner?->name} يطلب التعامل معك بعمولة {$this->pct($partnership->commission_pct)}% وأجور {$this->pct($partnership->wage_pct)}%.",
            null, ['target' => 'partnerships', 'partnership_id' => $partnership->id]);
    }

    public function partnershipAnswered(DalalPartnership $partnership): void
    {
        $accepted = $partnership->status === DalalPartnership::ACCEPTED;

        $this->notify($partnership->owner, $accepted ? self::PARTNERSHIP_ACCEPTED : self::PARTNERSHIP_REJECTED,
            $accepted ? 'تم قبول طلب التعامل' : 'تم رفض طلب التعامل',
            $accepted
                ? "قبل {$partnership->dalal?->name} التعامل معك بعمولة {$this->pct($partnership->commission_pct)}% وأجور {$this->pct($partnership->wage_pct)}%."
                : "رفض {$partnership->dalal?->name} طلب التعامل".($partnership->response_note ? ": {$partnership->response_note}" : '.'),
            null, ['target' => 'dalals', 'partnership_id' => $partnership->id]);
    }

    /**
     * الدلال باع من مصيد المالك: يُبلَّغ بالوزن وصافيه بعد العمولة والأجور.
     */
    public function dalalSold(Sale $sale, User $owner, float $kg, float $net): void
    {
        $this->notify($owner, self::DALAL_SOLD, 'بيع من مصيدك',
            "باع {$sale->seller?->name} ".number_format($kg, 1).' كجم من مصيدك بالفاتورة '.$sale->invoice_number.' — صافيك '.number_format($net, 2).' ر.س.',
            null, ['target' => 'dalals', 'sale_id' => $sale->id]);
    }

    public function payoutRecorded(DalalPayout $payout): void
    {
        $this->notify($payout->owner, self::PAYOUT_RECEIVED, 'دفعة من الدلال',
            "سجّل {$payout->dalal?->name} دفعة لك بمبلغ ".number_format((float) $payout->amount, 2).' ر.س.',
            null, ['target' => 'dalals', 'payout_id' => $payout->id]);
    }

    /**
     * المالك راجع سطور مصيده في فاتورة الدلال: قبلها أو رفضها بسبب.
     */
    public function invoiceReviewed(DalalInvoiceReview $review): void
    {
        $number = $review->sale?->invoice_number;
        $accepted = $review->isAccepted();

        $this->notify($review->dalal, $accepted ? self::INVOICE_ACCEPTED : self::INVOICE_REJECTED,
            $accepted ? 'قبل المالك فاتورتك' : 'رفض المالك فاتورتك',
            $accepted
                ? "قبل {$review->owner?->name} الفاتورة {$number}."
                : "رفض {$review->owner?->name} الفاتورة {$number}: {$review->reason}",
            null, ['target' => 'sale', 'sale_id' => $review->sale_id, 'review_id' => $review->id]);
    }

    /**
     * الدلال ردّ على فاتورة رفضها المالك فعادت إلى مراجعته.
     */
    public function invoiceReplied(DalalInvoiceReview $review): void
    {
        $this->notify($review->owner, self::INVOICE_REPLIED, 'رد الدلال على فاتورة مرفوضة',
            "ردّ {$review->dalal?->name} على رفضك الفاتورة {$review->sale?->invoice_number}: {$review->dalal_reply}",
            null, ['target' => 'dalal_invoice', 'sale_id' => $review->sale_id, 'review_id' => $review->id]);
    }

    /**
     * المالك سجّل أنه استلم دفعة من الدلال — تظهر في حسابه عند الدلال.
     */
    public function receiptRecorded(DalalPayout $payout): void
    {
        $this->notify($payout->dalal, self::RECEIPT_RECORDED, 'سجّل المالك استلام دفعة',
            "سجّل {$payout->owner?->name} أنه استلم منك ".number_format((float) $payout->amount, 2).' ر.س.',
            null, ['target' => 'owners', 'payout_id' => $payout->id]);
    }

    /*
     * شركة التشغيل وعدّادوها. المتقدّم لا حساب له قبل الاعتماد، فقرار طلبه
     * يصله رسالة نصية (CounterApplicationReview) لا إشعار تطبيق.
     */

    /**
     * طلب توظيف وُثّق جواله: يُبلَّغ به موظفو الشركة المفعّلون.
     */
    public function counterApplied(CounterApplication $application): void
    {
        $staff = User::where('operating_company_id', $application->operating_company_id)
            ->where('active', true)
            ->whereHas('appRole', fn ($q) => $q->where('key', Role::COMPANY))
            ->get();

        foreach ($staff as $user) {
            $this->notify($user, self::COUNTER_APPLIED, 'طلب توظيف عدّاد',
                "تقدّم {$application->name} لوظيفة عدّاد في {$application->port?->name} — راجعه من طلبات التوظيف.",
                null, ['target' => 'applications', 'application_id' => $application->id]);
        }
    }

    public function counterTransferred(StatisticsOfficer $officer, Port $from): void
    {
        $this->notify($officer->user, self::COUNTER_TRANSFERRED, 'نُقلت إلى ميناء آخر',
            "نُقل عملك من {$from->name} إلى {$officer->port?->name} — طابورك الآن طابور الميناء الجديد.");
    }

    private function pct(mixed $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    }

    /**
     * عدّادو ميناء عودة الرحلة المفعّلون — لا أحد إن لم يكن للميناء عدّاد
     * بحساب تطبيق، فالرحلة تبقى في طابور الإحصاء الميداني كما كانت.
     *
     * @return Collection<int, User>
     */
    private function countersOf(Trip $trip): Collection
    {
        $portId = $trip->return_port_id ?? $trip->departure_port_id;

        if ($portId === null) {
            return collect();
        }

        return User::query()
            ->where('active', true)
            ->whereHas('appRole', fn ($q) => $q->where('key', Role::COUNTER))
            ->whereHas('statisticsOfficer', fn ($q) => $q->where('port_id', $portId))
            ->get();
    }

    private function ownerUnlessActor(Trip $trip, ?User $by): ?User
    {
        return $by !== null && $by->id === $trip->owner_id ? null : $trip->owner;
    }
}
