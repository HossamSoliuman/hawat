<?php

namespace App\Models;

use Database\Factories\OtpCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;

/**
 * رمز تحقق أُرسل إلى جوال. صالح لدقائق معدودة، يُتحقّق منه مرة ثم يُستهلك عند
 * تغيير كلمة المرور — فلا يُعاد استعماله ولا يُخمَّن بعد خمس محاولات.
 */
class OtpCode extends BaseModel
{
    /** @use HasFactory<OtpCodeFactory> */
    use HasFactory;

    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    /** توثيق جوال المتقدّم لوظيفة عدّاد قبل أن يصل طلبه الشركة. */
    public const PURPOSE_COUNTER_APPLICATION = 'counter_application';

    public const MAX_ATTEMPTS = 5;

    public const TTL_MINUTES = 10;

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * يولّد رمزًا جديدًا للجوال ويُبطل ما قبله للغرض نفسه. يعيد الرمز الصريح
     * مرة واحدة ليُرسَل؛ الجدول لا يحفظ إلا تجزئته.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(string $phone, string $purpose = self::PURPOSE_PASSWORD_RESET, ?int $ttlMinutes = null): array
    {
        static::where('phone', $phone)->where('purpose', $purpose)->whereNull('used_at')->delete();

        // بيئة الاختبار بلا رسائل نصية تضبط رمزًا ثابتًا؛ الإنتاج لا يقبله أبدًا.
        $fixed = app()->isProduction() ? null : config('hawat.fixed_otp');
        $code = $fixed ? (string) $fixed : (string) random_int(100000, 999999);

        $otp = static::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($ttlMinutes ?? self::TTL_MINUTES),
        ]);

        return [$otp, $code];
    }

    public static function latestFor(string $phone, string $purpose = self::PURPOSE_PASSWORD_RESET): ?self
    {
        return static::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('id')
            ->first();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isLocked(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * يقارن الرمز المُدخل ويسجّل المحاولة. يعيد true عند التطابق.
     */
    public function attempt(string $code): bool
    {
        if ($this->isExpired() || $this->isLocked()) {
            return false;
        }

        if (! Hash::check($code, $this->code_hash)) {
            $this->increment('attempts');

            return false;
        }

        $this->forceFill(['verified_at' => now()])->save();

        return true;
    }
}
