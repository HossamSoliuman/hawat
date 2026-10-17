<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * إعادة ضبط بيئة الاختبار لا تعمل خارجها، والرمز الثابت لا يُقبل في الإنتاج.
 */
class StagingResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_reset_refuses_to_run_outside_staging(): void
    {
        $this->artisan('hawat:staging-reset')
            ->expectsOutputToContain('لبيئة الاختبار وحدها')
            ->assertFailed();
    }

    public function test_the_fixed_otp_applies_outside_production_only(): void
    {
        config(['hawat.fixed_otp' => '123456']);

        [, $code] = OtpCode::issue('0500000101');
        $this->assertSame('123456', $code);

        $this->app['env'] = 'production';
        [, $code] = OtpCode::issue('0500000101');
        $this->assertNotSame('123456', $code);
    }
}
