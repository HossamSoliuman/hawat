<?php

namespace App\Http\Requests\Owner;

use Illuminate\Validation\Rule;

/**
 * مخرجات المصيد: سطر لكل صنف. يرسلها المالك عن رحلته من التطبيق أو الويب.
 */
class CatchRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.species_id' => ['required', Rule::exists('species', 'id')],
            'items.*.weight_kg' => ['required', 'numeric', 'min:0.01'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
