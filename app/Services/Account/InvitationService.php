<?php

namespace App\Services\Account;

use App\Models\OtpCode;
use App\Models\User;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * دعوة حساب بلا كلمة مرور: المالك ينشئ الكابتن فيصل جواله رمز تفعيل برسالة
 * نصية، والكابتن يفعّل حسابه من التطبيق بجواله والرمز وكلمة مرور يختارها
 * (POST /auth/activate). لا كلمة مرور يعرفها المالك في أي لحظة: الحساب
 * المدعوّ يحمل كلمة عشوائية لا تُعرض ولا تُرسل، فلا يدخل أحد قبل التفعيل.
 *
 * الدعوة صالحة User::INVITATION_HOURS من آخر إرسال، وخمس محاولات للرمز؛
 * بعدها يعيد المالك إرسالها.
 */
class InvitationService
{
    public function __construct(private readonly SmsSender $sms) {}

    /**
     * كلمة مرور الحساب المدعوّ قبل تفعيله — عشوائية ولا تُحفظ في أي مكان آخر.
     */
    public static function placeholderPassword(): string
    {
        return Str::random(64);
    }

    public function send(User $user, ?User $invitedBy = null): void
    {
        $user->forceFill(['invited_at' => now(), 'activated_at' => null])->save();

        [, $code] = OtpCode::issue($user->phone, OtpCode::PURPOSE_ACCOUNT_INVITATION, User::INVITATION_HOURS * 60);

        $from = $invitedBy ? " من {$invitedBy->name}" : '';
        $days = intdiv(User::INVITATION_HOURS, 24);

        $this->sms->send($user->phone, "دعوة{$from} للعمل كابتن في تطبيق حوات. رمز التفعيل: {$code} — صالح {$days} أيام. افتح التطبيق واختر «تفعيل حساب» ثم أدخل جوالك والرمز واختر كلمة مرورك.");
    }

    /**
     * يتحقق من رمز الدعوة ويضبط كلمة المرور التي اختارها صاحب الحساب.
     */
    public function activate(string $phone, string $code, string $password): User
    {
        $user = User::where('phone', $phone)->whereNotNull('invited_at')->whereNull('activated_at')->first();
        $otp = OtpCode::latestFor($phone, OtpCode::PURPOSE_ACCOUNT_INVITATION);

        // جوال غير مدعوّ أو بلا رمز: الرسالة نفسها، فلا يُستدلّ من الردّ على الحسابات.
        if (! $user || ! $otp) {
            throw ValidationException::withMessages(['code' => 'لا توجد دعوة سارية لهذا الجوال.']);
        }

        if ($otp->isExpired()) {
            throw ValidationException::withMessages(['code' => 'انتهت صلاحية الدعوة — اطلب من المالك إعادة إرسالها.']);
        }

        if ($otp->isLocked()) {
            throw ValidationException::withMessages(['code' => 'تجاوزت عدد المحاولات — اطلب من المالك إعادة إرسال الدعوة.']);
        }

        if (! $otp->attempt($code)) {
            throw ValidationException::withMessages(['code' => 'الرمز غير صحيح.']);
        }

        if (! $user->active) {
            throw ValidationException::withMessages(['phone' => 'هذا الحساب معطّل — راجع المالك.']);
        }

        return DB::transaction(function () use ($user, $otp, $password) {
            $user->forceFill(['password' => $password, 'activated_at' => now()])->save();
            $otp->forceFill(['used_at' => now()])->save();

            return $user;
        });
    }

    /**
     * من استعاد كلمة مروره برمز إلى جواله أثبت ملكيته للجوال واختار كلمته —
     * فهو تفعيل لدعوته إن كانت معلّقة.
     */
    public function markActivatedByPasswordReset(User $user): void
    {
        if ($user->isPendingInvitation()) {
            $user->forceFill(['activated_at' => now()])->save();
            OtpCode::where('phone', $user->phone)->where('purpose', OtpCode::PURPOSE_ACCOUNT_INVITATION)->whereNull('used_at')->delete();
        }
    }
}
