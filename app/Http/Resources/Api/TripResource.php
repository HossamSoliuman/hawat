<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * الرحلة كما يراها المالك والعدّاد: الحالة بمفردات الوزارة وتسميتها في التطبيق وخطوة التقدّم.
 */
class TripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_number' => $this->trip_number,
            'status' => $this->status,
            'app_status' => $this->app_status,
            'progress_step' => $this->progress_step,
            'sale_status' => $this->sale_status,
            'can_sell' => $this->canSell(),
            'boat' => $this->whenLoaded('boat', fn () => $this->boat ? ['id' => $this->boat->id, 'name' => $this->boat->name, 'boat_number' => $this->boat->boat_number] : null),
            // العلاقة المحمّلة الفارغة تُرجع null من whenLoaded دون استدعاء الدالة،
            // فالرجوع إلى العمود النصي القديم (captain_name / statistics_officer) يمرّ بـ when.
            'captain' => $this->when($this->relationLoaded('captain'), fn () => $this->captain ? ['id' => $this->captain->id, 'name' => $this->captain->name, 'phone' => $this->captain->phone] : ['id' => null, 'name' => $this->captain_name, 'phone' => null]),
            'counter' => $this->when($this->relationLoaded('counter'), fn () => $this->counter ? ['id' => $this->counter->id, 'name' => $this->counter->name] : ($this->statistics_officer ? ['id' => null, 'name' => $this->statistics_officer] : null)),
            'departure_port' => $this->whenLoaded('departurePort', fn () => $this->departurePort ? ['id' => $this->departurePort->id, 'name' => $this->departurePort->name] : null),
            'return_port' => $this->whenLoaded('returnPort', fn () => $this->returnPort ? ['id' => $this->returnPort->id, 'name' => $this->returnPort->name] : null),
            'trip_type' => $this->whenLoaded('tripType', fn () => $this->tripType ? ['id' => $this->tripType->id, 'name' => $this->tripType->name] : null),
            'crew_count' => (int) $this->crew_count,
            'planned_days' => $this->planned_days,
            'gear_type' => $this->gear_type,
            'license_number' => $this->license_number,
            'departure_time' => $this->departure_time?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'return_time' => $this->return_time?->toIso8601String(),
            'catch_submitted_at' => $this->catch_submitted_at?->toIso8601String(),
            'received_at' => $this->received_at?->toIso8601String(),
            'counted_at' => $this->counted_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'duration_hours' => $this->duration_hours !== null ? (float) $this->duration_hours : null,
            'captain_input_kg' => $this->captain_input_kg !== null ? (float) $this->captain_input_kg : null,
            'actual_weight_kg' => $this->actual_weight_kg !== null ? (float) $this->actual_weight_kg : null,
            'diff_kg' => $this->diff_kg !== null ? (float) $this->diff_kg : null,
            'approved_kg' => $this->approved_kg !== null ? (float) $this->approved_kg : null,
            'notes' => $this->notes,
            'catch_records' => CatchRecordResource::collection($this->whenLoaded('catchRecords')),
            'available_stock' => $this->when(isset($this->available_stock), fn () => $this->available_stock),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
