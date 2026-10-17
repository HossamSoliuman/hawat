<?php

namespace App\Http\Requests\Owner;

use App\Models\Fisher;
use Illuminate\Validation\Rule;

/**
 * الكابتن حساب دخول (users) فوق سجلّ صياد (fishers) — النموذج الواحد يملأ الاثنين.
 *
 * كلمة المرور اختيارية: بلا كلمة مرور يُدعى الكابتن برمز تفعيل إلى جواله
 * ويختار كلمته بنفسه، وبها يُنشأ الحساب مفعّلًا كما كان.
 */
class CaptainRequest extends OwnerRequest
{
    public function rules(): array
    {
        // معرّف الكابتن في المسار (التعديل) أو null (الإنشاء).
        $captain = $this->route('captain');
        $fisherId = $captain ? Fisher::where('user_id', $captain)->value('id') : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^05\d{8}$/', Rule::unique('users', 'phone')->ignore($captain)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($captain)],
            'password' => ['nullable', 'string', 'min:8'],
            'national_id' => ['required', 'string', 'max:50', Rule::unique('fishers', 'national_id')->ignore($fisherId)],
            'id_type_id' => ['nullable', $this->lookup('id_types')],
            'nationality' => ['nullable', 'string', 'max:100'],
            'boat_id' => ['nullable', $this->owned('boats')],
            'port_id' => ['required_without:boat_id', 'nullable', Rule::exists('ports', 'id')],
            ...$this->fisherDocumentRules(),
            'experience_years' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'phone.unique' => 'رقم الجوال مسجّل لحساب آخر في حوات.',
            'national_id.unique' => 'رقم الهوية مسجّل لصياد آخر في حوات.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'الاسم', 'phone' => 'الجوال', 'national_id' => 'رقم الهوية', 'port_id' => 'الميناء', 'boat_id' => 'القارب'];
    }
}
