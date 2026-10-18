<?php

namespace App\Services\Stock;

use App\Models\StockMovement;
use App\Models\StockMovementType;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * دفتر المخزون — المصدر الوحيد لـ"الوزن المتاح" في التطبيق ومخزون الدلال
 * وتقرير حركات الصنف.
 *
 * كل ما يدخل مخزون حائز (مالك أو دلال) أو يخرج منه يُكتب سطرًا هنا بوزن
 * موجب أو سالب، فالمتاح من صنف في رحلة = مجموع سطور الحائز عليهما. لا
 * عمود "رصيد" يُحدَّث في مكان آخر حتى لا يختلف الدفتر عن الواقع.
 */
class StockLedger
{
    public const INTAKE = 'إدخال مصيد';

    public const SALE = 'بيع';

    public const CONSIGN_OUT = 'إرسال لدلال';

    public const CONSIGN_IN = 'استلام من مالك';

    public const RETURN = 'مرتجع';

    public const ADJUSTMENT = 'تسوية';

    /**
     * يكتب حركة ويحسب رصيد الحائز على الصنف بعدها (عبر كل الرحلات).
     */
    public function record(
        User $holder,
        int $speciesId,
        string $type,
        float $weightKg,
        ?Trip $trip = null,
        ?Model $reference = null,
        ?User $by = null,
        ?string $notes = null,
    ): StockMovement {
        $balance = round($this->balance($holder, $speciesId) + $weightKg, 2);

        return StockMovement::create([
            'holder_id' => $holder->id,
            'species_id' => $speciesId,
            'trip_id' => $trip?->id,
            'stock_movement_type_id' => StockMovementType::named($type)->id,
            'weight_kg' => round($weightKg, 2),
            'balance_after' => $balance,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'user_id' => $by?->id,
            'notes' => $notes,
        ]);
    }

    /**
     * رصيد الحائز على صنف عبر كل الرحلات.
     */
    public function balance(User $holder, int $speciesId): float
    {
        return (float) StockMovement::forHolder($holder)->where('species_id', $speciesId)->sum('weight_kg');
    }

    /**
     * المتاح من صنف واحد في رحلة بعينها.
     */
    public function availableFor(User $holder, Trip $trip, int $speciesId): float
    {
        return (float) StockMovement::forHolder($holder)
            ->where('trip_id', $trip->id)
            ->where('species_id', $speciesId)
            ->sum('weight_kg');
    }

    /**
     * المتاح في رحلة لكل صنف: [species_id => kg] بلا الأصناف المنتهية.
     */
    public function availableByTrip(User $holder, Trip $trip): Collection
    {
        return StockMovement::forHolder($holder)
            ->where('trip_id', $trip->id)
            ->selectRaw('species_id, SUM(weight_kg) AS kg')
            ->groupBy('species_id')
            ->pluck('kg', 'species_id')
            ->map(fn ($kg) => round((float) $kg, 2))
            ->filter(fn ($kg) => $kg > 0);
    }

    /**
     * سطور الرحلة كما تعرضها شاشة البيع: كل صنف معدود ومتاحه الآن (صفر إن نفد).
     *
     * @return array<int, array{species_id:int, species:string|null, available_kg:float}>
     */
    public function availableLines(User $holder, Trip $trip): array
    {
        $available = $this->availableByTrip($holder, $trip);

        // سطر لكل صنف: الصنف المسجّل في سطرين (عدٌّ قديم) لا يُعرض مرتين ولا يُجمع مرتين.
        return $trip->catchRecords()->with('species:id,name_ar')->get()
            ->unique('species_id')
            ->map(fn ($record) => [
                'species_id' => $record->species_id,
                'species' => $record->species?->name_ar,
                'available_kg' => $available[$record->species_id] ?? 0.0,
            ])->values()->all();
    }

    /**
     * الأسماك المتوفرة عند الحائز مجموعةً بالصنف (شاشة "الأسماك المتوفرة").
     */
    public function stockBySpecies(User $holder): Collection
    {
        return StockMovement::forHolder($holder)
            ->with('species:id,name_ar,name_sci')
            ->selectRaw('species_id, SUM(weight_kg) AS kg')
            ->groupBy('species_id')
            ->having('kg', '>', 0)
            ->orderByDesc('kg')
            ->get();
    }

    /**
     * هل بقي للحائز شيء من مصيد الرحلة؟
     */
    public function hasStock(User $holder, Trip $trip): bool
    {
        return $this->availableByTrip($holder, $trip)->isNotEmpty();
    }
}
