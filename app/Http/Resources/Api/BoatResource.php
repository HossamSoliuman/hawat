<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_en' => $this->name_en,
            'boat_number' => $this->boat_number,
            'status' => $this->status,
            'port' => $this->whenLoaded('port', fn () => ['id' => $this->port->id, 'name' => $this->port->name]),
            'category' => $this->whenLoaded('category', fn () => $this->category ? ['id' => $this->category->id, 'name' => $this->category->name] : null),
            'type' => $this->whenLoaded('type', fn () => $this->type ? ['id' => $this->type->id, 'name' => $this->type->name] : null),
            'captain' => $this->whenLoaded('captainUser', fn () => $this->captainUser ? ['id' => $this->captainUser->id, 'name' => $this->captainUser->name, 'phone' => $this->captainUser->phone] : null),
            'length_m' => $this->length_m !== null ? (float) $this->length_m : null,
            'width_m' => $this->width_m !== null ? (float) $this->width_m : null,
            'color' => $this->color,
            'hull_number' => $this->hull_number,
            'body_type' => $this->body_type,
            'engine_type' => $this->engine_type,
            'engine_power' => $this->engine_power,
            'engine_status' => $this->engine_status,
            'call_sign' => $this->call_sign,
            'serial_number' => $this->serial_number,
            'capacity' => $this->capacity,
            'crew_count' => (int) $this->crew_count,
            'setup' => $this->when($this->relationLoaded('captainUser'), fn () => $this->setup()),
            'crew_capacity' => (int) $this->crew_capacity,
            'license' => [
                'type' => $this->license_type,
                'number' => $this->license_number,
                'status' => $this->license_status,
                'process' => $this->license_process,
                'area' => $this->license_area,
                'date' => $this->license_date?->toDateString(),
                'expiry' => $this->license_expiry?->toDateString(),
            ],
            'next_inspection_date' => $this->next_inspection_date?->toDateString(),
            'trips_count' => (int) $this->trips_count,
            'total_catch_kg' => (float) $this->total_catch_kg,
            'version' => (int) $this->version,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
