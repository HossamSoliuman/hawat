<?php

namespace App\Services\Trips;

use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Fisher;
use App\Models\Trip;
use App\Models\User;
use App\Services\Notifications\Notifier;
use App\Services\Stock\StockLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * دورة الرحلة من الإنشاء إلى اكتمال العد — مسار واحد تستدعيه بوابة المالك
 * وواجهة التطبيق وبوابة العدّاد وصفحة الإحصاء الميداني، فتبقى الرحلة الواحدة
 * صادقة في كل لوحة: الحالة بمفردات الوزارة، والمخزون في الدفتر، وحالة
 * القارب، والإشعار عند كل انتقال (Notifier).
 *
 * الكابتن سجلّ صياد عند المالك بلا حساب: المالك ينشئ الرحلة ويبدؤها ويرسل
 * مخرجاتها أو يلغيها عنه.
 */
class TripService
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly Notifier $notifier,
    ) {}

    /**
     * ينشئ المالك الرحلة ويسندها لكابتن (كابتن القارب إن لم يختر) → مجدولة.
     * القارب يحدّد الميناء والمالك، والكابتن يُملأ اسمه في العمود النصي الذي
     * تقرؤه صفحات الوزارة.
     */
    public function create(User $owner, array $data): Trip
    {
        $boat = Boat::forOwner($owner)->findOrFail($data['boat_id']);
        $captain = $this->captainOf($owner, $data['captain_id'] ?? $boat->captain_id);

        $trip = Trip::create([
            'trip_number' => Trip::nextNumber(),
            'owner_id' => $owner->id,
            'boat_id' => $boat->id,
            'captain_id' => $captain?->id,
            'captain_name' => $captain?->name ?? $boat->captain,
            'departure_port_id' => $data['departure_port_id'] ?? $boat->port_id,
            'return_port_id' => $data['return_port_id'] ?? $data['departure_port_id'] ?? $boat->port_id,
            'trip_type_id' => $data['trip_type_id'] ?? null,
            'crew_count' => $data['crew_count'] ?? $boat->crew_count,
            'departure_time' => $data['departure_time'] ?? null,
            'planned_days' => $data['planned_days'] ?? null,
            'gear_type' => $data['gear_type'] ?? null,
            'license_number' => $data['license_number'] ?? $boat->license_number,
            'notes' => $data['notes'] ?? null,
            'status' => Trip::SCHEDULED,
            'sale_status' => Trip::SALE_NOT_STARTED,
        ]);

        return $trip->setRelation('boat', $boat)->setRelation('captain', $captain);
    }

    /**
     * التعديل مباح ما دامت الرحلة لم تنطلق.
     */
    public function update(Trip $trip, array $data): Trip
    {
        $this->assertStatus($trip, [Trip::SCHEDULED], 'لا يمكن تعديل رحلة انطلقت.');

        if (array_key_exists('captain_id', $data)) {
            $captain = $this->captainOf($trip->owner, $data['captain_id']);
            $data['captain_name'] = $captain?->name;
        }

        $trip->update(collect($data)->only([
            'boat_id', 'captain_id', 'captain_name', 'departure_port_id', 'return_port_id', 'trip_type_id',
            'crew_count', 'departure_time', 'planned_days', 'gear_type', 'license_number', 'notes',
        ])->all());

        return $trip->unsetRelation('captain')->unsetRelation('boat');
    }

    /**
     * المالك يبدأ الرحلة → في البحر، والقارب يظهر "في البحر" على الخريطة.
     */
    public function start(Trip $trip, ?User $by = null): Trip
    {
        $this->assertStatus($trip, [Trip::SCHEDULED], 'الرحلة ليست بانتظار الانطلاق.');

        return DB::transaction(function () use ($trip, $by) {
            $trip->update([
                'status' => Trip::AT_SEA,
                'started_at' => now(),
                'departure_time' => $trip->departure_time ?? now(),
            ]);
            $trip->boat?->update(['status' => Trip::AT_SEA]);

            $this->log('بدء رحلة', $trip, $by, 'انطلقت الرحلة');
            $this->notifier->tripStarted($trip, $by);

            return $trip;
        });
    }

    /**
     * الإلغاء بسبب إلزامي، قبل أن يُرسَل المصيد.
     */
    public function cancel(Trip $trip, string $reason, ?User $by = null): Trip
    {
        $this->assertStatus($trip, [Trip::SCHEDULED, Trip::AT_SEA], 'لا يمكن إلغاء رحلة أُرسل مصيدها.');

        return DB::transaction(function () use ($trip, $reason, $by) {
            $wasAtSea = $trip->status === Trip::AT_SEA;

            $trip->update([
                'status' => Trip::CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            if ($wasAtSea) {
                $trip->boat?->update(['status' => 'نشط']);
            }

            $this->log('إلغاء رحلة', $trip, $by, "سبب الإلغاء: {$reason}");
            $this->notifier->tripCancelled($trip, $by);

            return $trip;
        });
    }

    /**
     * المالك يرسل مخرجات المصيد عن كابتنه → بانتظار الإحصاء. سطر لكل صنف
     * بالوزن المعلن (captain_kg: ما أعلنته الرحلة قبل العد)، والمجموع يُكتب في
     * captain_input_kg الذي يقرؤه الإحصاء الميداني.
     *
     * @param  array<int, array{species_id:int, weight_kg:float|string, notes?:string|null}>  $items
     */
    public function submitCatch(Trip $trip, array $items, ?User $by = null): Trip
    {
        $this->assertStatus($trip, [Trip::AT_SEA], 'لا تُرسل المخرجات إلا لرحلة في البحر.');

        if ($items === []) {
            throw ValidationException::withMessages(['items' => 'أضف صنفًا واحدًا على الأقل.']);
        }

        return DB::transaction(function () use ($trip, $items, $by) {
            $trip->catchRecords()->delete();

            $total = 0.0;
            foreach ($this->mergeBySpecies($items) as $item) {
                $kg = round((float) $item['weight_kg'], 2);
                $total += $kg;

                CatchRecord::create([
                    'trip_id' => $trip->id,
                    'species_id' => $item['species_id'],
                    'quantity_kg' => $kg,
                    'captain_kg' => $kg,
                    'captain_notes' => $item['notes'] ?? null,
                    'recorded_at' => now()->toDateString(),
                    'added_by' => $by?->id,
                ]);
            }

            $returned = now();
            $trip->update([
                'status' => Trip::AWAITING_COUNT,
                'captain_input_kg' => round($total, 2),
                'catch_submitted_at' => $returned,
                'return_time' => $returned,
                'duration_hours' => $trip->started_at ? round($trip->started_at->diffInMinutes($returned) / 60, 2) : null,
            ]);
            $trip->boat?->update(['status' => 'نشط']);

            $this->log('إرسال مخرجات', $trip, $by, 'المصيد المعلن '.round($total, 2).' كجم');
            $this->notifier->catchSubmitted($trip, $by);
            $this->notifier->countRequested($trip);

            return $trip;
        });
    }

    /**
     * العدّاد يستلم الرحلة → تحت الإحصاء.
     */
    public function receive(Trip $trip, User $counter): Trip
    {
        $this->assertStatus($trip, [Trip::RETURNED, Trip::AWAITING_COUNT], 'الرحلة ليست بانتظار العدّاد.');

        $trip->update([
            'status' => Trip::COUNTING,
            'counter_id' => $counter->id,
            'statistics_officer' => $counter->name,
            'received_at' => now(),
        ]);

        $this->log('استلام للعد', $trip, $counter, 'بدأ العد');

        return $trip;
    }

    /**
     * اكتمال العد → بانتظار الاعتماد، ويُفتح مصيد الرحلة للبيع.
     *
     * `$counted` أوزان العدّاد لكل صنف
     * [species_id => ['weight_kg' =>, 'notes' =>, 'verified' =>]]. صنف ليس في
     * مخرجات الكابتن يُضاف سطرًا جديدًا منسوبًا إلى العدّاد ("إضافة صنف إن
     * وجد" في شاشة العد)، وصنف أعلنه الكابتن ولم يُعدّ يبقى على وزنه.
     *
     * حين يأتي العد من صفحة الإحصاء الميداني بوزن إجمالي فقط تُترك أوزان
     * الأصناف على ما أعلنه الكابتن ويُكتب الإجمالي كما قاسه الموظف.
     */
    public function count(Trip $trip, ?array $counted = null, ?User $counter = null, ?float $totalKg = null, ?string $notes = null): Trip
    {
        // إعادة العد قبل الاعتماد مباحة لتصحيح الوزن؛ المخزون أُدخل في أول عدّ ولا يُكرَّر.
        $this->assertStatus($trip, [Trip::RETURNED, Trip::AWAITING_COUNT, Trip::COUNTING, Trip::AWAITING_APPROVAL], 'الرحلة ليست في طور العد.');

        $firstCount = $trip->counted_at === null;

        return DB::transaction(function () use ($trip, $counted, $counter, $totalKg, $notes, $firstCount) {
            $sum = 0.0;
            foreach ($trip->catchRecords as $record) {
                $entry = $counted[$record->species_id] ?? null;
                $kg = $entry !== null ? round((float) $entry['weight_kg'], 2) : (float) ($record->captain_kg ?? $record->quantity_kg);
                $sum += $kg;

                $record->update([
                    'counted_kg' => $kg,
                    'quantity_kg' => $kg,
                    'counter_notes' => $entry['notes'] ?? $record->counter_notes,
                    // مربّع "فحص الكمية" في شاشة العد؛ العد بوزن إجمالي يعتمد السطور كلها.
                    'verified' => $entry !== null ? (bool) ($entry['verified'] ?? true) : ($counted === null || (bool) $record->verified),
                    'corrected_by' => $entry !== null && $kg !== (float) $record->captain_kg ? $counter?->id : $record->corrected_by,
                ]);
            }

            $sum += $this->addCountedSpecies($trip, $counted ?? [], $counter);

            $actual = $totalKg ?? $sum;
            $trip->update([
                'status' => Trip::AWAITING_APPROVAL,
                'counter_id' => $counter?->id ?? $trip->counter_id,
                'statistics_officer' => $counter?->name ?? $trip->statistics_officer,
                'actual_weight_kg' => round($actual, 2),
                'diff_kg' => round($actual - (float) $trip->captain_input_kg, 2),
                'counted_at' => now(),
                'notes' => $notes ?? $trip->notes,
            ]);

            $this->openForSale($trip);

            $this->log('إحصاء', $trip, $counter, 'الوزن الفعلي '.round($actual, 2).' كجم بفرق '.$trip->diff_kg.' كجم');

            // إعادة العد تصحيح لا حدث جديد — الإشعار عند أول اكتمال فقط.
            if ($firstCount) {
                $this->notifier->countCompleted($trip);
                $counter?->statisticsOfficer?->increment('trips_counted');
            }

            return $trip;
        });
    }

    /**
     * أصناف عدّها العدّاد ولم يعلنها الكابتن: سطر جديد بوزن معدود بلا وزن
     * كابتن، منسوب إلى من أضافه. يعيد مجموع أوزانها.
     */
    private function addCountedSpecies(Trip $trip, array $counted, ?User $counter): float
    {
        $declared = $trip->catchRecords->pluck('species_id')->all();
        $added = 0.0;

        foreach ($counted as $speciesId => $entry) {
            if (in_array((int) $speciesId, $declared, true)) {
                continue;
            }

            $kg = round((float) $entry['weight_kg'], 2);
            $added += $kg;

            CatchRecord::create([
                'trip_id' => $trip->id,
                'species_id' => (int) $speciesId,
                'quantity_kg' => $kg,
                'counted_kg' => $kg,
                'counter_notes' => $entry['notes'] ?? null,
                'verified' => (bool) ($entry['verified'] ?? true),
                'recorded_at' => now()->toDateString(),
                'added_by' => $counter?->id,
            ]);
        }

        // السطور الجديدة تدخل العلاقة المحمّلة حتى يقرأها openForSale بعدها.
        if ($added > 0) {
            $trip->unsetRelation('catchRecords');
        }

        return $added;
    }

    /**
     * يُدخل مصيد الرحلة المعدود في دفتر المالك مرة واحدة ويفتح البيع.
     * الرحلات القديمة بلا مالك لا تُفتح — لا حائز لها.
     */
    public function openForSale(Trip $trip): void
    {
        $owner = $trip->owner;

        if ($owner === null || $trip->stockMovements()->exists()) {
            return;
        }

        foreach ($trip->catchRecords()->get() as $record) {
            $kg = (float) ($record->counted_kg ?? $record->quantity_kg);
            if ($kg > 0) {
                $this->ledger->record($owner, $record->species_id, StockLedger::INTAKE, $kg, $trip, $record, $trip->counter, 'اكتمل العد');
            }
        }

        $trip->update(['sale_status' => $this->ledger->hasStock($owner, $trip) ? Trip::SALE_OPEN : Trip::SALE_DONE]);
    }

    /**
     * بعد كل بيع أو إرسال: إن نفد مصيد الرحلة صارت "مباعة".
     */
    public function refreshSaleStatus(Trip $trip): void
    {
        if ($trip->sale_status === Trip::SALE_NOT_STARTED || $trip->owner === null) {
            return;
        }

        $trip->update(['sale_status' => $this->ledger->hasStock($trip->owner, $trip) ? Trip::SALE_OPEN : Trip::SALE_DONE]);
    }

    /**
     * الكابتن لا بدّ أن يكون من كباتن هذا المالك.
     */
    private function captainOf(User $owner, ?int $captainId): ?Fisher
    {
        if ($captainId === null) {
            return null;
        }

        return Fisher::forOwner($owner)->captains()->find($captainId)
            ?? throw ValidationException::withMessages(['captain_id' => 'الكابتن المختار ليس من كباتنك.']);
    }

    /**
     * الصنف الواحد لا يُكرَّر سطرين: تُجمع أوزانه وتُلحق ملاحظاته.
     */
    private function mergeBySpecies(array $items): array
    {
        $merged = [];
        foreach ($items as $item) {
            $id = (int) $item['species_id'];
            if (! isset($merged[$id])) {
                $merged[$id] = ['species_id' => $id, 'weight_kg' => 0.0, 'notes' => null];
            }
            $merged[$id]['weight_kg'] += (float) $item['weight_kg'];
            if (! empty($item['notes'])) {
                $merged[$id]['notes'] = trim(($merged[$id]['notes'] ?? '').' '.$item['notes']);
            }
        }

        return array_values($merged);
    }

    private function assertStatus(Trip $trip, array $allowed, string $message): void
    {
        if (! in_array($trip->status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function log(string $action, Trip $trip, ?User $by, string $details): void
    {
        AuditLog::create([
            'user_email' => $by?->email ?? $by?->phone,
            'role' => $by?->app_role_key ?? 'system',
            'action' => $action,
            'entity' => 'Trip',
            'record_label' => $trip->trip_number,
            'details' => $details,
            'ip' => request()?->ip(),
        ]);
    }
}
