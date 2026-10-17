<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * عامل في دكة الدلال.
 */
class DalalWorkerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'nationality' => $this->nationality,
            'id_type' => $this->whenLoaded('idType', fn () => $this->idType ? ['id' => $this->idType->id, 'name' => $this->idType->name] : null),
            'id_number' => $this->id_number,
            'type' => $this->whenLoaded('type', fn () => $this->type ? ['id' => $this->type->id, 'name' => $this->type->name] : null),
            'status' => $this->status,
            'avatar_url' => $this->avatar_url,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
