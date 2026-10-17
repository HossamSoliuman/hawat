<?php

namespace App\Http\Requests\Owner;

use App\Models\Fisher;
use Illuminate\Validation\Rule;

/**
 * الكابتن سجلّ صياد عند المالك بلا حساب دخول — حقوله حقول فرد الطاقم، والجوال
 * اختياري. `status` يعطّله دون حذف.
 */
class CaptainRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^05\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'national_id' => ['required', 'string', 'max:50', Rule::unique('fishers', 'national_id')->ignore($this->route('captain'))],
            'id_type_id' => ['nullable', $this->lookup('id_types')],
            'nationality' => ['nullable', 'string', 'max:100'],
            'boat_id' => ['nullable', $this->owned('boats')],
            'port_id' => ['required_without:boat_id', 'nullable', Rule::exists('ports', 'id')],
            ...$this->fisherDocumentRules(),
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(Fisher::STATUSES)],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'national_id.unique' => 'رقم الهوية مسجّل لصياد آخر في حوات.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'الاسم', 'phone' => 'الجوال', 'national_id' => 'رقم الهوية', 'port_id' => 'الميناء', 'boat_id' => 'القارب'];
    }
}
