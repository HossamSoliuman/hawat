<?php

namespace App\Http\Controllers;

use App\Models\RegistrationRequest;
use App\Models\Role;
use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * نموذج "انضم إلى حوات" في صفحة الهبوط: مالك قوارب أو دلال يطلب حسابًا.
 * الطلب يُحفظ بانتظار المدير العام (Panel\RegistrationRequestController)،
 * ولا يُنشأ الحساب إلا عند الاعتماد.
 */
class RegistrationRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // الجوال يُطبَّع قبل التحقق حتى يُطابق الصيغة المخزّنة في الحسابات.
        $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);

        $back = route('landing').'#register';

        $validator = Validator::make($request->all(), [
            'role' => ['required', Rule::in(RegistrationRequest::ROLES)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required', 'string', 'regex:/^05\d{8}$/',
                Rule::unique('users', 'phone'),
                Rule::unique('registration_requests', 'phone')->where('status', RegistrationRequest::PENDING),
            ],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'boats_count' => ['nullable', 'integer', 'min:1', 'max:999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'g-recaptcha-response' => Recaptcha::enabled() ? ['required', new Recaptcha('register')] : [],
        ], [
            'g-recaptcha-response.required' => 'تعذّر التحقق من أنك لست روبوتًا، أعد المحاولة.',
            'role.required' => 'اختر نوع الحساب: مالك قوارب أو دلال.',
            'role.in' => 'اختر نوع الحساب: مالك قوارب أو دلال.',
            'name.required' => 'اكتب اسمك الكامل.',
            'name.max' => 'الاسم أطول من المسموح.',
            'phone.required' => 'اكتب رقم جوالك.',
            'phone.regex' => 'رقم الجوال يجب أن يكون بصيغة 05XXXXXXXX.',
            'phone.unique' => 'هذا الجوال مسجّل لدينا أو له طلب قيد المراجعة.',
            'email.email' => 'البريد الإلكتروني غير صحيح.',
            'email.max' => 'البريد الإلكتروني أطول من المسموح.',
            'business_name.max' => 'اسم المنشأة أطول من المسموح.',
            'city.max' => 'اسم المدينة أطول من المسموح.',
            'boats_count.integer' => 'عدد القوارب رقم من 1 إلى 999.',
            'boats_count.min' => 'عدد القوارب رقم من 1 إلى 999.',
            'boats_count.max' => 'عدد القوارب رقم من 1 إلى 999.',
            'notes.max' => 'الملاحظات أطول من 2000 حرف.',
            'password.required' => 'اختر كلمة مرور.',
            'password.min' => 'كلمة المرور 8 أحرف على الأقل.',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين.',
        ]);

        if ($validator->fails()) {
            return redirect()->to($back)
                ->withErrors($validator, 'register')
                ->withInput($request->except('password', 'password_confirmation', 'g-recaptcha-response'));
        }

        $data = $validator->validated();
        $role = Role::where('key', $data['role'])->where('active', true)->firstOrFail();

        RegistrationRequest::create([
            'role_id' => $role->id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'business_name' => $data['business_name'] ?? null,
            'city' => $data['city'] ?? null,
            // عدد القوارب سؤال المالك وحده.
            'boats_count' => $data['role'] === Role::OWNER ? ($data['boats_count'] ?? null) : null,
            'notes' => $data['notes'] ?? null,
            'password' => $data['password'],
            'ip' => $request->ip(),
        ]);

        return redirect()->to($back)->with('registered', $data['name']);
    }
}
