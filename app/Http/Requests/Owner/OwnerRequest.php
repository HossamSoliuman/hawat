<?php

namespace App\Http\Requests\Owner;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * أصل طلبات بوابة المالك — الويب والـAPI يتحققان بالقواعد نفسها. الدخول
 * محكوم بالوسيط (panel:owner / api.role:owner) فالتفويض هنا دائمًا صحيح،
 * والقواعد تقيّد المعرّفات بما يملكه المالك نفسه.
 */
abstract class OwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function owner(): User
    {
        return $this->user();
    }

    /**
     * قيد "موجود ويملكه هذا المالك" على جدول بعمود مالك.
     */
    protected function owned(string $table, string $column = 'owner_id'): Exists
    {
        return Rule::exists($table, 'id')->where($column, $this->owner()->id);
    }

    protected function lookup(string $table): Exists
    {
        return Rule::exists($table, 'id')->where('active', true);
    }

    /**
     * تواريخ وثائق الصياد (كابتن أو طاقم): الإقامة والرخصة، كلها اختيارية،
     * والانتهاء لا يسبق البداية حين تُرسل البداية.
     */
    protected function fisherDocumentRules(): array
    {
        return [
            'iqama_issue_date' => ['nullable', 'date'],
            'iqama_expiry_date' => ['nullable', 'date', Rule::when($this->filled('iqama_issue_date'), 'after_or_equal:iqama_issue_date')],
            'license_number' => ['nullable', 'string', 'max:100'],
            'license_date' => ['nullable', 'date'],
            'license_expiry' => ['nullable', 'date', Rule::when($this->filled('license_date'), 'after_or_equal:license_date')],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge(['phone' => User::normalizePhone($this->input('phone'))]);
        }
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'رقم الجوال يجب أن يكون بصيغة 05XXXXXXXX.',
            'iqama_expiry_date.after_or_equal' => 'تاريخ انتهاء الإقامة يجب أن يكون في تاريخ بدايتها أو بعده.',
            'license_expiry.after_or_equal' => 'تاريخ انتهاء الرخصة يجب أن يكون في تاريخ بدايتها أو بعده.',
            'items.required' => 'أضف صنفًا واحدًا على الأقل.',
            'items.*.species_id.required' => 'اختر نوع السمك.',
            'items.*.weight_kg.min' => 'الوزن يجب أن يكون أكبر من صفر.',
            'items.*.price_per_kg.min' => 'سعر الكيلو يجب أن يكون أكبر من صفر.',
        ];
    }
}
