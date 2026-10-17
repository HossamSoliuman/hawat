<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Sms\SmsSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * استعادة كلمة المرور بالجوال على ثلاث خطوات كما في التطبيق: طلب الرمز، ثم
 * التحقق منه، ثم كلمة المرور الجديدة.
 *
 * طلب الرمز يردّ بالرسالة نفسها وُجد الجوال أم لا، حتى لا تُستكشف الحسابات.
 * والرمز صالح عشر دقائق وخمس محاولات ولمرة واحدة (App\Models\OtpCode).
 */
class PasswordResetController extends Controller
{
    public function __construct(private readonly SmsSender $sms) {}

    public function sendCode(Request $request): JsonResponse
    {
        $phone = $this->phone($request);

        $user = User::where('phone', $phone)->whereNotNull('role_id')->where('active', true)->first();

        if ($user) {
            [, $code] = OtpCode::issue($phone);

            $this->sms->send($phone, "رمز التحقق لتطبيق حوات: {$code} — صالح لعشر دقائق.");
        }

        return response()->json([
            'message' => 'إن كان الجوال مسجّلًا فقد أُرسل إليه رمز التحقق.',
            'data' => ['expires_in' => OtpCode::TTL_MINUTES * 60],
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $phone = $this->phone($request);

        $data = $request->validate(['code' => ['required', 'digits:6']]);

        $this->check($phone, $data['code']);

        return response()->json(['message' => 'الرمز صحيح.', 'data' => ['verified' => true]]);
    }

    public function reset(Request $request): JsonResponse
    {
        $phone = $this->phone($request);

        $data = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $otp = $this->check($phone, $data['code']);

        $user = User::where('phone', $phone)->firstOrFail();
        $user->update(['password' => $data['password']]);

        // الرمز استُهلك، وكل الأجهزة تخرج حتى يدخل من يعرف الكلمة الجديدة.
        $otp->forceFill(['used_at' => now()])->save();
        $user->tokens()->delete();

        return response()->json(['message' => 'تم تغيير كلمة المرور — سجّل الدخول بها.']);
    }

    private function phone(Request $request): string
    {
        $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);

        return $request->validate([
            'phone' => ['required', 'string', 'regex:/^05\d{8}$/'],
        ], ['phone.regex' => 'رقم الجوال يجب أن يكون بصيغة 05XXXXXXXX.'])['phone'];
    }

    private function check(string $phone, string $code): OtpCode
    {
        $otp = OtpCode::latestFor($phone);

        if (! $otp || $otp->isExpired()) {
            throw ValidationException::withMessages(['code' => 'انتهت صلاحية الرمز — اطلب رمزًا جديدًا.']);
        }

        if ($otp->isLocked()) {
            throw ValidationException::withMessages(['code' => 'تجاوزت عدد المحاولات — اطلب رمزًا جديدًا.']);
        }

        if (! $otp->attempt($code)) {
            throw ValidationException::withMessages(['code' => 'الرمز غير صحيح.']);
        }

        return $otp;
    }
}
