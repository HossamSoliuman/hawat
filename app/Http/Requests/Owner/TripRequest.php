<?php

namespace App\Http\Requests\Owner;

use Illuminate\Validation\Rule;

class TripRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'boat_id' => ['required', $this->owned('boats')],
            'captain_id' => ['nullable', $this->ownedCaptain()],
            'departure_port_id' => ['nullable', Rule::exists('ports', 'id')],
            'return_port_id' => ['nullable', Rule::exists('ports', 'id')],
            'trip_type_id' => ['nullable', $this->lookup('trip_types')],
            'crew_count' => ['nullable', 'integer', 'min:0', 'max:200'],
            'departure_time' => ['nullable', 'date'],
            'planned_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'gear_type' => ['nullable', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['boat_id' => 'القارب', 'captain_id' => 'الكابتن', 'departure_time' => 'وقت الانطلاق'];
    }
}
