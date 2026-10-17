<?php

namespace App\Http\Resources\Api;

use App\Models\CatchRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * سطر في سجل صيد المالك: الصنف ووزنه المعلن والمعدود ورحلته وقاربها وكابتنها.
 *
 * @mixin CatchRecord
 */
class CatchLogEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'species' => $this->whenLoaded('species', fn () => ['id' => $this->species->id, 'name' => $this->species->name_ar, 'name_sci' => $this->species->name_sci]),
            'trip' => $this->whenLoaded('trip', fn () => [
                'id' => $this->trip->id,
                'trip_number' => $this->trip->trip_number,
                'status' => $this->trip->status,
                'app_status' => $this->trip->app_status,
                'boat' => $this->trip->boat?->name,
                'captain' => $this->trip->captain?->name ?? $this->trip->captain_name,
                'return_time' => $this->trip->return_time?->toIso8601String(),
            ]),
            'captain_kg' => $this->captain_kg !== null ? (float) $this->captain_kg : null,
            'counted_kg' => $this->counted_kg !== null ? (float) $this->counted_kg : null,
            'captain_notes' => $this->captain_notes,
            'counter_notes' => $this->counter_notes,
            'verified' => (bool) $this->verified,
            'recorded_at' => $this->recorded_at?->toDateString(),
        ];
    }
}
