<?php

namespace App\Http\Resources\Api;

use App\Services\Dalal\DalalProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * إعدادات الدلال: ملفه التجاري والدكة، والشركة وشعارها، وعمالة الدكة.
 */
class DalalProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ref = fn ($model) => $model ? ['id' => $model->id, 'name' => $model->name] : null;

        return [
            'id_number' => $this->id_number,
            'region' => $ref($this->region),
            'governorate' => $ref($this->governorate),
            'port' => $ref($this->port),
            'dakka_name' => $this->dakka_name,
            'dakka_number' => $this->dakka_number,
            'company_name' => $this->company_name,
            'cr_number' => $this->cr_number,
            'vat_number' => $this->vat_number,
            'company_email' => $this->company_email,
            'company_phone' => $this->company_phone,
            'address' => $this->address,
            'website' => $this->website,
            'logo_url' => $this->logo_url,
            ...$this->workforce($ref),
        ];
    }

    /**
     * أعداد العمالة حسب النوع والجنسية: `records` محسوبة من العمال النشطين
     * (/dalal/workers)، و`counts` سطور الأعداد القديمة قبل تسجيلهم أفرادًا.
     */
    private function workforce(callable $ref): array
    {
        if (! $this->relationLoaded('user')) {
            return [];
        }

        $workforce = app(DalalProfileService::class)->workforce($this->user);

        return [
            'workers_source' => $workforce['source'],
            'workers' => $workforce['rows']->map(fn (array $row) => [
                'id' => $row['id'],
                'type' => $ref($row['type']),
                'nationality' => $row['nationality'],
                'count' => $row['count'],
                'notes' => $row['notes'],
            ])->values(),
        ];
    }
}
