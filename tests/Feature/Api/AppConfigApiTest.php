<?php

namespace Tests\Feature\Api;

use Spectator\Spectator;
use Tests\TestCase;

/**
 * إعداد التطبيق قبل الدخول: يُقرأ بلا رمز، ويحمل النسخة الدنيا ورقم الدعم،
 * و"نسيت كلمة المرور" يحوّل إلى الدعم ما دامت الرسائل النصية غير مفعّلة.
 */
class AppConfigApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');
    }

    public function test_the_app_reads_its_config_without_a_token(): void
    {
        config([
            'app.url' => 'https://hawat.sa',
            'hawat.mobile_app.min_version' => '1.2.0',
            'hawat.mobile_app.support_phone' => '0595233393',
            'hawat.mobile_app.privacy_url' => null,
        ]);

        $this->getJson('/api/v1/app/config')
            ->assertValidRequest()
            ->assertValidResponse(200)
            ->assertJsonPath('data.min_version', '1.2.0')
            ->assertJsonPath('data.sms_enabled', false)
            ->assertJsonPath('data.support_phone', '0595233393')
            ->assertJsonPath('data.join_url', 'https://hawat.sa/#register')
            ->assertJsonPath('data.privacy_url', null);
    }
}
