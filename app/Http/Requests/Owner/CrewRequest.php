<?php

namespace App\Http\Requests\Owner;

use Illuminate\Validation\Rule;

class CrewRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^05\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'national_id' => ['required', 'string', 'max:50', Rule::unique('fishers', 'national_id')->ignore($this->route('crew'))],
            'id_type_id' => ['nullable', $this->lookup('id_types')],
            'nationality' => ['nullable', 'string', 'max:100'],
            'fisher_role_id' => ['nullable', $this->lookup('fisher_roles')],
            'boat_id' => ['nullable', $this->owned('boats')],
            'port_id' => ['required_without:boat_id', 'nullable', Rule::exists('ports', 'id')],
            ...$this->fisherDocumentRules(),
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['نشط', 'غير نشط'])],
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
        return ['name' => 'الاسم', 'national_id' => 'رقم الهوية', 'port_id' => 'الميناء'];
    }
}
