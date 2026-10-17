<?php

namespace App\Http\Requests\Owner;

use App\Models\Boat;
use Closure;
use Illuminate\Validation\Rule;

/**
 * - `crew_count` لا يُقبل: عدد الطاقم يُحسب من أفراده المسجّلين على القارب.
 * - `capacity` الحمولة بالطن (تقبل الكسور).
 * - `license_date` بداية الرخصة، و`license_expiry` لا يسبقها.
 * - `status` اختياري في التعديل (غيابه = كما هي). "في البحر" تضبطه الرحلات
 *   وحدها: لا يُختار يدويًا، والقارب في رحلة لا تتغيّر حالته حتى تنتهي.
 */
class BoatRequest extends OwnerRequest
{
    public function rules(): array
    {
        $boat = $this->route('boat');
        $current = $boat ? Boat::whereKey($boat)->value('status') : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'boat_number' => ['required', 'string', 'max:50', Rule::unique('boats', 'boat_number')->ignore($boat)],
            'port_id' => ['required', Rule::exists('ports', 'id')],
            'boat_category_id' => ['nullable', $this->lookup('boat_categories')],
            'boat_type_id' => ['nullable', $this->lookup('boat_types')],
            'captain_id' => ['nullable', $this->ownedCaptain()],
            'status' => ['nullable', Rule::in(Boat::STATUSES), $this->statusRule($current)],
            'length_m' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'width_m' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'color' => ['nullable', 'string', 'max:50'],
            'hull_number' => ['nullable', 'string', 'max:100'],
            'body_type' => ['nullable', 'string', 'max:100'],
            'engine_type' => ['nullable', 'string', 'max:100'],
            'engine_power' => ['nullable', 'string', 'max:100'],
            'engine_status' => ['nullable', 'string', 'max:100'],
            'call_sign' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'capacity' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'crew_capacity' => ['nullable', 'integer', 'min:0'],
            'license_type' => ['nullable', 'string', 'max:100'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_status' => ['nullable', 'string', 'max:50'],
            'license_process' => ['nullable', 'string', 'max:100'],
            'license_area' => ['nullable', 'string', 'max:255'],
            'license_date' => ['nullable', 'date'],
            'license_expiry' => ['nullable', 'date', Rule::when($this->filled('license_date'), 'after_or_equal:license_date')],
            'next_inspection_date' => ['nullable', 'date'],
        ];
    }

    /**
     * القيمة المرسلة تُقبل إن كانت الحالة الحالية نفسها (النموذج كاملًا يعيدها
     * كما هي)؛ وإلا فلا "في البحر" يدويًا، ولا تغيير لقارب في رحلة.
     */
    private function statusRule(?string $current): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($current) {
            if ($value === $current) {
                return;
            }

            if ($current === Boat::AT_SEA) {
                $fail('القارب في رحلة الآن — تتغيّر حالته حين تنتهي الرحلة.');
            } elseif ($value === Boat::AT_SEA) {
                $fail('حالة «في البحر» تُضبط تلقائيًا حين تبدأ الرحلة، ولا تُختار يدويًا.');
            }
        };
    }

    /**
     * التعديل بلا حالة يُبقيها كما هي — لا تُكتب null فوقها.
     */
    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key === null && array_key_exists('status', $data) && $data['status'] === null) {
            unset($data['status']);
        }

        return $data;
    }

    public function messages(): array
    {
        return parent::messages() + [
            'boat_number.unique' => 'رقم القارب مسجّل لقارب آخر في حوات.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'اسم القارب', 'boat_number' => 'رقم القارب', 'port_id' => 'الميناء', 'captain_id' => 'الكابتن'];
    }
}
