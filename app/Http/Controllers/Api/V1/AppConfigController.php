<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * ما يحتاجه التطبيق قبل الدخول: النسخة الدنيا للتحديث الإجباري، ورابط
 * المتجر، وهل استعادة كلمة المرور بالرسائل متاحة، وأرقام الدعم. بلا رمز.
 */
class AppConfigController extends Controller
{
    public function show(): JsonResponse
    {
        $app = config('hawat.mobile_app');

        return response()->json(['data' => [
            'min_version' => (string) $app['min_version'],
            'latest_version' => (string) $app['latest_version'],
            'store_url' => $app['store_url'] ?: null,
            'sms_enabled' => (bool) $app['sms_enabled'],
            'support_phone' => $app['support_phone'] ?: null,
            'support_whatsapp' => $app['support_whatsapp'] ?: null,
            // نموذج الانضمام في صفحة الهبوط، على النطاق الرئيسي لا على مسار الطلب.
            'join_url' => $app['join_url'] ?: rtrim((string) config('app.url'), '/').'/#register',
            'privacy_url' => $app['privacy_url'] ?: null,
            'terms_url' => $app['terms_url'] ?: null,
        ]])->header('Cache-Control', 'public, max-age=300');
    }
}
