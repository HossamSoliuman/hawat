<?php

namespace App\Services\Sales;

use App\Models\AuditLog;
use App\Models\Consignment;
use App\Models\ConsignmentItem;
use App\Models\Customer;
use App\Models\DalalInvoiceReview;
use App\Models\PaymentStatus;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Trip;
use App\Models\User;
use App\Services\Dalal\DalalStock;
use App\Services\Dalal\PartnershipService;
use App\Services\Notifications\Notifier;
use App\Services\Stock\StockLedger;
use App\Services\Trips\TripService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * بيع المصيد وإرساله للدلال — كلاهما يخصم من دفتر المالك، والإرسال يضيف
 * إلى دفتر الدلال بالوزن نفسه. الوزن المطلوب لا يتجاوز المتاح في الرحلة.
 * والدلال يبيع من مخزونه (sellFromStock) فيخصم من دفتره ويُقتطع من كل سطر
 * عمولته وأجوره بنِسب اتفاقه المقبول مع مالك المصيد.
 */
class SaleService
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly TripService $trips,
        private readonly DalalStock $dalalStock,
        private readonly PartnershipService $partnerships,
        private readonly Notifier $notifier,
    ) {}

    /**
     * المالك يبيع مصيد رحلة لزبون.
     *
     * @param  array{trip_id:int, customer_id?:int|null, payment_method_id?:int|null, payment_status_id?:int|null, discount?:float, paid_amount?:float|null, notes?:string|null, items:array<int, array{species_id:int, weight_kg:float|string, price_per_kg:float|string}>}  $data
     */
    public function sell(User $seller, array $data): Sale
    {
        $trip = Trip::forOwner($seller)->findOrFail($data['trip_id']);
        $this->assertSellable($trip);

        $items = $this->checkedItems($seller, $trip, $data['items'] ?? []);

        if (! empty($data['customer_id'])) {
            Customer::forAccount($seller)->findOrFail($data['customer_id']);
        }

        return DB::transaction(function () use ($seller, $trip, $data, $items) {
            $subtotal = round(array_sum(array_column($items, 'total')), 2);
            // الخصم لا يتجاوز المجموع والمدفوع لا يتجاوز الإجمالي، فلا يصير المتبقي سالبًا.
            $discount = min(round((float) ($data['discount'] ?? 0), 2), $subtotal);
            $total = round($subtotal - $discount, 2);
            $paid = array_key_exists('paid_amount', $data) && $data['paid_amount'] !== null ? min(round((float) $data['paid_amount'], 2), $total) : $total;

            $sale = Sale::create([
                'invoice_number' => Sale::nextInvoiceNumber(),
                'seller_id' => $seller->id,
                'trip_id' => $trip->id,
                'customer_id' => $data['customer_id'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'payment_status_id' => $data['payment_status_id'] ?? $this->paymentStatusFor($paid, $total)->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'owner_net' => $total,
                'status' => Sale::COMPLETED,
                'sold_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $factor = $subtotal > 0 ? $total / $subtotal : 0;

            foreach ($items as $item) {
                SaleItem::create(['sale_id' => $sale->id, 'trip_id' => $trip->id, 'owner_id' => $seller->id, 'owner_net' => round($item['total'] * $factor, 2)] + $item);
                $this->ledger->record($seller, $item['species_id'], StockLedger::SALE, -$item['weight_kg'], $trip, $sale, $seller, "فاتورة {$sale->invoice_number}");
            }

            $this->trips->refreshSaleStatus($trip);

            $this->log('بيع', $seller, $sale->invoice_number, "بيع مصيد الرحلة {$trip->trip_number} بإجمالي {$total}");

            return $sale->load('items.species', 'customer', 'paymentMethod', 'paymentStatus', 'trip');
        });
    }

    /**
     * المالك يرسل مصيد رحلة إلى مخزون دلال.
     *
     * @param  array{trip_id:int, dalal_id:int, notes?:string|null, items:array<int, array{species_id:int, weight_kg:float|string}>}  $data
     */
    public function consign(User $owner, array $data): Consignment
    {
        $trip = Trip::forOwner($owner)->findOrFail($data['trip_id']);
        $this->assertSellable($trip);

        $dalal = User::whereKey($data['dalal_id'])->where('active', true)
            ->whereHas('appRole', fn ($q) => $q->where('key', Role::DALAL))
            ->first();

        if ($dalal === null) {
            throw ValidationException::withMessages(['dalal_id' => 'اختر دلالًا مفعّلًا.']);
        }

        $items = $this->checkedItems($owner, $trip, $data['items'] ?? [], priced: false);

        return DB::transaction(function () use ($owner, $dalal, $trip, $data, $items) {
            $consignment = Consignment::create([
                'consignment_number' => Consignment::nextNumber(),
                'owner_id' => $owner->id,
                'dalal_id' => $dalal->id,
                'trip_id' => $trip->id,
                'boat_id' => $trip->boat_id,
                'total_kg' => round(array_sum(array_column($items, 'weight_kg')), 2),
                'status' => Consignment::SENT,
                'sent_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                ConsignmentItem::create(['consignment_id' => $consignment->id, 'species_id' => $item['species_id'], 'weight_kg' => $item['weight_kg']]);
                $this->ledger->record($owner, $item['species_id'], StockLedger::CONSIGN_OUT, -$item['weight_kg'], $trip, $consignment, $owner, "إرسال {$consignment->consignment_number} إلى {$dalal->name}");
                $this->ledger->record($dalal, $item['species_id'], StockLedger::CONSIGN_IN, $item['weight_kg'], $trip, $consignment, $owner, "استلام {$consignment->consignment_number} من {$owner->name}");
            }

            $this->trips->refreshSaleStatus($trip);

            $this->log('إرسال لدلال', $owner, $consignment->consignment_number, "إرسال {$consignment->total_kg} كجم من الرحلة {$trip->trip_number} إلى {$dalal->name}");

            $consignment->load('items.species', 'dalal', 'trip')->setRelation('owner', $owner);
            $this->notifier->stockReceived($consignment);

            return $consignment;
        });
    }

    /**
     * الدلال يبيع من مخزونه لزبون.
     *
     * السطر يذكر الصنف ووزنه وسعره، واختياريًا رحلته (دفعة بعينها). بلا رحلة
     * يُصرف الوزن من دفعات الصنف الأقدم استلامًا أوّلًا وقد ينقسم على أكثر من
     * رحلة ومالك — كل جزء سطر فاتورة بمالكه. العمولة والأجور تُحسب على صافي
     * السطر بعد توزيع الخصم على السطور بنسبة قيمها.
     *
     * @param  array{customer_id?:int|null, payment_method_id?:int|null, discount?:float|null, paid_amount?:float|null, notes?:string|null, items:array<int, array{species_id:int, weight_kg:float|string, price_per_kg:float|string, trip_id?:int|null}>}  $data
     */
    public function sellFromStock(User $dalal, array $data): Sale
    {
        if (! empty($data['customer_id'])) {
            Customer::forAccount($dalal)->findOrFail($data['customer_id']);
        }

        $lines = $this->allocate($dalal, $data['items'] ?? []);

        return DB::transaction(function () use ($dalal, $data, $lines) {
            $subtotal = round(array_sum(array_column($lines, 'total')), 2);
            $discount = min(round((float) ($data['discount'] ?? 0), 2), $subtotal);
            $total = round($subtotal - $discount, 2);
            $paid = array_key_exists('paid_amount', $data) && $data['paid_amount'] !== null ? min(round((float) $data['paid_amount'], 2), $total) : $total;
            $factor = $subtotal > 0 ? $total / $subtotal : 0;

            $terms = [];
            foreach ($lines as $i => $line) {
                $key = $line['owner_id'] ?? 0;
                $terms[$key] ??= $this->partnerships->termsFor($line['owner_id'], $dalal);
                $net = round($line['total'] * $factor, 2);
                $lines[$i]['commission_amount'] = round($net * $terms[$key]['commission_pct'] / 100, 2);
                $lines[$i]['wage_amount'] = round($net * $terms[$key]['wage_pct'] / 100, 2);
                $lines[$i]['owner_net'] = round($net - $lines[$i]['commission_amount'] - $lines[$i]['wage_amount'], 2);
            }

            $tripIds = array_values(array_unique(array_column($lines, 'trip_id')));
            // نِسب الفاتورة تُحفظ حين يكون مصيدها لمالك واحد؛ وإلا تبقى في سطورها.
            $single = count($terms) === 1 ? reset($terms) : ['commission_pct' => 0, 'wage_pct' => 0];

            $sale = Sale::create([
                'invoice_number' => Sale::nextInvoiceNumber(),
                'seller_id' => $dalal->id,
                'trip_id' => count($tripIds) === 1 ? $tripIds[0] : null,
                'customer_id' => $data['customer_id'] ?? null,
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'payment_status_id' => $this->paymentStatusFor($paid, $total)->id,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'paid_amount' => $paid,
                'commission_pct' => $single['commission_pct'],
                'commission_amount' => round(array_sum(array_column($lines, 'commission_amount')), 2),
                'wage_pct' => $single['wage_pct'],
                'wage_amount' => round(array_sum(array_column($lines, 'wage_amount')), 2),
                'owner_net' => round(array_sum(array_column($lines, 'owner_net')), 2),
                'status' => $paid >= $total ? Sale::COMPLETED : Sale::IN_PROGRESS,
                'sold_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $trips = Trip::whereIn('id', array_filter($tripIds))->get()->keyBy('id');

            foreach ($lines as $line) {
                SaleItem::create(['sale_id' => $sale->id] + $line);
                $this->ledger->record($dalal, $line['species_id'], StockLedger::SALE, -$line['weight_kg'], $trips[$line['trip_id']] ?? null, $sale, $dalal, "فاتورة {$sale->invoice_number}");
            }

            $sale->setRelation('seller', $dalal);
            foreach (collect($lines)->groupBy('owner_id') as $ownerId => $ownerLines) {
                if ($owner = User::find($ownerId)) {
                    // سطور كل مالك في الفاتورة "فاتورة دلال" عنده بانتظار مراجعته.
                    DalalInvoiceReview::create(['sale_id' => $sale->id, 'owner_id' => $owner->id, 'dalal_id' => $dalal->id, 'status' => DalalInvoiceReview::PENDING]);
                    $this->notifier->dalalSold($sale, $owner, (float) $ownerLines->sum('weight_kg'), (float) $ownerLines->sum('owner_net'));
                }
            }

            $this->log('بيع دلال', $dalal, $sale->invoice_number, "بيع من مخزون الدلال بإجمالي {$total} — عمولة {$sale->commission_amount} وأجور {$sale->wage_amount}");

            return $sale->load('items.species', 'items.trip', 'items.owner', 'customer', 'paymentMethod', 'paymentStatus', 'trip');
        });
    }

    /**
     * يسجّل دفعة على فاتورة لم تُسدَّد كاملة، فتكتمل حين يُسدَّد المتبقي.
     * فاتورة المالك تبقى "مكتمل" وإن بقي فيها مبلغ؛ حالة الدفع تحمل التحصيل.
     */
    public function recordPayment(User $by, Sale $sale, float $amount): Sale
    {
        $amount = round($amount, 2);

        return DB::transaction(function () use ($by, $sale, $amount) {
            // دفعتان من جهازين معًا لا تتجاوزان المتبقي.
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($amount <= 0 || $amount > $sale->remaining) {
                throw ValidationException::withMessages(['amount' => 'المبلغ يجب أن يكون أكبر من صفر ولا يتجاوز المتبقي ('.number_format($sale->remaining, 2).' ر.س).']);
            }

            $paid = round((float) $sale->paid_amount + $amount, 2);

            $sale->update([
                'paid_amount' => $paid,
                'payment_status_id' => $this->paymentStatusFor($paid, (float) $sale->total)->id,
                'status' => $paid >= (float) $sale->total ? Sale::COMPLETED : $sale->status,
            ]);

            $this->log('تحصيل دفعة', $by, $sale->invoice_number, "تحصيل {$amount} — المدفوع {$paid} من {$sale->total}");

            return $sale;
        });
    }

    /**
     * يوزّع سطور بيع الدلال على دفعات مخزونه: سطر برحلة يُصرف منها وحدها،
     * وسطر بلا رحلة يُصرف من دفعات صنفه الأقدم أوّلًا. لا يتجاوز مجموع ما
     * يُصرف من دفعة متاحها.
     *
     * @return array<int, array{species_id:int, trip_id:int|null, owner_id:int|null, weight_kg:float, price_per_kg:float, total:float}>
     */
    private function allocate(User $dalal, array $items): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'أضف صنفًا واحدًا على الأقل.']);
        }

        // المتاح لكل دفعة يُنقص كلما صُرف منها سطر، فسطران من الصنف نفسه لا يتجاوزانه معًا.
        $lots = $this->dalalStock->lots($dalal)->map(fn ($lot) => $lot + ['left' => $lot['available_kg']])->all();
        $lines = [];

        foreach ($items as $index => $item) {
            $speciesId = (int) $item['species_id'];
            $kg = round((float) $item['weight_kg'], 2);
            $price = round((float) ($item['price_per_kg'] ?? 0), 2);
            $tripId = isset($item['trip_id']) && $item['trip_id'] !== '' ? (int) $item['trip_id'] : null;

            if ($kg <= 0) {
                throw ValidationException::withMessages(["items.{$index}.weight_kg" => 'الوزن يجب أن يكون أكبر من صفر.']);
            }
            if ($price <= 0) {
                throw ValidationException::withMessages(["items.{$index}.price_per_kg" => 'سعر الكيلو يجب أن يكون أكبر من صفر.']);
            }

            $candidates = array_keys(array_filter($lots, fn ($lot) => $lot['species_id'] === $speciesId && ($tripId === null || $lot['trip_id'] === $tripId)));
            $available = round(array_sum(array_map(fn ($key) => $lots[$key]['left'], $candidates)), 2);

            if ($kg > $available) {
                throw ValidationException::withMessages([
                    "items.{$index}.weight_kg" => 'الوزن المطلوب يتجاوز المتاح في مخزونك ('.$available.' كجم).',
                ]);
            }

            $remaining = $kg;
            foreach ($candidates as $key) {
                if ($remaining <= 0) {
                    break;
                }

                $take = round(min($remaining, $lots[$key]['left']), 2);
                if ($take <= 0) {
                    continue;
                }

                $lots[$key]['left'] = round($lots[$key]['left'] - $take, 2);
                $remaining = round($remaining - $take, 2);

                $lines[] = [
                    'species_id' => $speciesId,
                    'trip_id' => $lots[$key]['trip_id'],
                    'owner_id' => $lots[$key]['owner_id'],
                    'weight_kg' => $take,
                    'price_per_kg' => $price,
                    'total' => round($take * $price, 2),
                ];
            }
        }

        return $lines;
    }

    private function assertSellable(Trip $trip): void
    {
        if (! $trip->canSell()) {
            throw ValidationException::withMessages(['trip_id' => 'مصيد هذه الرحلة غير متاح للبيع — لم يكتمل العد أو نفد.']);
        }
    }

    /**
     * يدمج سطور الصنف الواحد ويتحقق أن المطلوب لا يتجاوز المتاح في الرحلة.
     *
     * @return array<int, array{species_id:int, weight_kg:float, price_per_kg?:float, total?:float}>
     */
    private function checkedItems(User $holder, Trip $trip, array $items, bool $priced = true): array
    {
        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'أضف صنفًا واحدًا على الأقل.']);
        }

        $available = $this->ledger->availableByTrip($holder, $trip);
        $merged = [];

        foreach ($items as $index => $item) {
            $id = (int) $item['species_id'];
            $kg = round((float) $item['weight_kg'], 2);

            if ($kg <= 0) {
                throw ValidationException::withMessages(["items.{$index}.weight_kg" => 'الوزن يجب أن يكون أكبر من صفر.']);
            }

            $merged[$id] ??= ['species_id' => $id, 'weight_kg' => 0.0];
            $merged[$id]['weight_kg'] = round($merged[$id]['weight_kg'] + $kg, 2);

            if ($merged[$id]['weight_kg'] > ($available[$id] ?? 0)) {
                throw ValidationException::withMessages([
                    "items.{$index}.weight_kg" => 'الوزن المطلوب يتجاوز المتاح ('.($available[$id] ?? 0).' كجم).',
                ]);
            }

            if ($priced) {
                $price = round((float) ($item['price_per_kg'] ?? 0), 2);
                if ($price <= 0) {
                    throw ValidationException::withMessages(["items.{$index}.price_per_kg" => 'سعر الكيلو يجب أن يكون أكبر من صفر.']);
                }
                // سطور الصنف الواحد بأسعار مختلفة تبقى سطورًا مستقلة في الفاتورة.
                $merged[$id]['lines'][] = ['species_id' => $id, 'weight_kg' => $kg, 'price_per_kg' => $price, 'total' => round($kg * $price, 2)];
            }
        }

        if (! $priced) {
            return array_values($merged);
        }

        return collect($merged)->flatMap(fn ($entry) => $entry['lines'])->values()->all();
    }

    private function paymentStatusFor(float $paid, float $total): PaymentStatus
    {
        return PaymentStatus::named(match (true) {
            $paid <= 0 && $total > 0 => 'غير مدفوع',
            $paid < $total => 'مدفوع جزئيًا',
            default => 'مدفوع',
        });
    }

    private function log(string $action, User $by, string $label, string $details): void
    {
        AuditLog::create([
            'user_email' => $by->email ?? $by->phone,
            'role' => $by->app_role_key ?? 'owner',
            'action' => $action,
            'entity' => 'Sale',
            'record_label' => $label,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
