<?php

namespace App\Services\Owner;

use App\Models\CatchRecord;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * سجل صيد المالك: كل سطر مصيد أعلنه على رحلاته وما عُدّ منه، وملخص بالصنف.
 * يُصفّى بالكابتن أو بالقارب — كان سجل الكابتن في بوابته قبل أن يصير
 * الكابتن سجلًّا عند المالك.
 *
 * @phpstan-type Filters array{captain_id?: int|string|null, boat_id?: int|string|null, search?: string|null}
 */
class CatchLog
{
    /**
     * @param  Filters  $filters
     */
    public function entries(User $owner, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $search = $filters['search'] ?? null;

        return CatchRecord::query()
            ->whereIn('trip_id', $this->trips($owner, $filters)->select('id'))
            ->with(['species', 'trip.boat', 'trip.captain'])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('trip', fn ($t) => $t->where('trip_number', 'like', "%{$search}%"))
                ->orWhereHas('species', fn ($s) => $s->where('name_ar', 'like', "%{$search}%"))))
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * مجموع المعلن والمعدود لكل صنف.
     *
     * @param  Filters  $filters
     * @return Collection<int, object{species_id:int, species:string, name_sci:?string, trips:int, captain_kg:float, counted_kg:float}>
     */
    public function summary(User $owner, array $filters = []): Collection
    {
        return CatchRecord::query()
            ->join('species', 'species.id', '=', 'catch_records.species_id')
            ->whereIn('catch_records.trip_id', $this->trips($owner, $filters)->select('id'))
            ->select('species.id AS species_id', 'species.name_ar AS species', 'species.name_sci AS name_sci')
            ->selectRaw('COUNT(DISTINCT catch_records.trip_id) AS trips')
            ->selectRaw('COALESCE(SUM(catch_records.captain_kg), 0) AS captain_kg')
            ->selectRaw('COALESCE(SUM(catch_records.counted_kg), 0) AS counted_kg')
            ->groupBy('species.id', 'species.name_ar', 'species.name_sci')
            ->orderByDesc(DB::raw('SUM(catch_records.captain_kg)'))
            ->get()
            ->map(fn ($row) => (object) [
                'species_id' => (int) $row->species_id,
                'species' => $row->species,
                'name_sci' => $row->name_sci,
                'trips' => (int) $row->trips,
                'captain_kg' => round((float) $row->captain_kg, 2),
                'counted_kg' => round((float) $row->counted_kg, 2),
            ]);
    }

    /**
     * @param  Collection<int, object>  $summary
     * @return array{captain_kg: float, counted_kg: float, species: int}
     */
    public function totals(Collection $summary): array
    {
        return [
            'captain_kg' => round((float) $summary->sum('captain_kg'), 2),
            'counted_kg' => round((float) $summary->sum('counted_kg'), 2),
            'species' => $summary->count(),
        ];
    }

    /**
     * @param  Filters  $filters
     */
    private function trips(User $owner, array $filters): Builder
    {
        return Trip::forOwner($owner)
            ->when($filters['captain_id'] ?? null, fn ($q, $id) => $q->where('captain_id', $id))
            ->when($filters['boat_id'] ?? null, fn ($q, $id) => $q->where('boat_id', $id));
    }
}
