<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

/**
 * صورة شخصية — للحساب نفسه، ولكابتن المالك وفرد طاقمه، ولعامل الدلال.
 * صورة فقط (JPG/PNG/WebP يُفحص محتواها لا امتدادها)، حتى 2 ميغابايت و4096
 * بكسل للضلع. لا مستندات ولا رخص.
 */
class AvatarRequest extends FormRequest
{
    /** القواعد نفسها للنماذج التي ترفع الصورة مع بقية الحقول (لوحة الويب). */
    public const RULES = ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'];

    public const MESSAGES = [
        'avatar.image' => 'الصورة الشخصية يجب أن تكون صورة JPG أو PNG أو WebP.',
        'avatar.mimes' => 'الصورة الشخصية يجب أن تكون صورة JPG أو PNG أو WebP.',
        'avatar.max' => 'حجم الصورة الشخصية يجب ألا يتجاوز 2 ميغابايت.',
        'avatar.dimensions' => 'أبعاد الصورة الشخصية يجب ألا تتجاوز 4096 بكسل للضلع.',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', ...self::RULES],
        ];
    }

    public function messages(): array
    {
        return self::MESSAGES;
    }

    public function attributes(): array
    {
        return ['avatar' => 'الصورة الشخصية'];
    }
}
