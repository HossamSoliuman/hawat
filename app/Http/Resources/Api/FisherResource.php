<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * سجلّ صياد (كابتن أو طاقم) كما يراه المالك — بلا حساب دخول. `version`
 * يُرسل في If-Match عند التعديل.
 */
class FisherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_captain' => (bool) $this->is_captain,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'national_id' => $this->national_id,
            'nationality' => $this->nationality,
            'id_type' => $this->whenLoaded('idType', fn () => $this->idType ? ['id' => $this->idType->id, 'name' => $this->idType->name] : null),
            'iqama_issue_date' => $this->iqama_issue_date?->toDateString(),
            'iqama_expiry_date' => $this->iqama_expiry_date?->toDateString(),
            'role' => $this->whenLoaded('fisherRole', fn () => $this->fisherRole ? ['id' => $this->fisherRole->id, 'name' => $this->fisherRole->name] : ['id' => null, 'name' => $this->role]),
            'boat' => $this->whenLoaded('boat', fn () => $this->boat ? ['id' => $this->boat->id, 'name' => $this->boat->name] : null),
            'port' => $this->whenLoaded('port', fn () => $this->port ? ['id' => $this->port->id, 'name' => $this->port->name] : null),
            'license_number' => $this->license_number,
            'license_status' => $this->license_status,
            'license_date' => $this->license_date?->toDateString(),
            'license_expiry' => $this->license_expiry?->toDateString(),
            'experience_years' => (int) $this->experience_years,
            'trips_count' => (int) $this->trips_count,
            'status' => $this->status,
            'version' => (int) $this->version,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
