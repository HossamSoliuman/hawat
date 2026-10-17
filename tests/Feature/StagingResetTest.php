<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
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

    public function test_under_a_path_prefix_panel_links_keep_the_prefix(): void
    {
        config(['app.url' => 'https://hawat.sa/staging']);
        $this->refreshApplicationRoutes();

        $request = Request::create('https://hawat.sa/staging/admin/login', 'GET', server: [
            'SCRIPT_NAME' => '/staging/index.php',
            'SCRIPT_FILENAME' => base_path('public/index.php'),
        ]);
        $this->app->instance('request', $request);
        url()->setRequest($request);

        $this->assertSame('https://hawat.sa/staging/admin/login', route('panel.login'));
    }

    private function refreshApplicationRoutes(): void
    {
        $router = $this->app['router'];
        $router->setRoutes(new RouteCollection);
        require base_path('routes/web.php');
        $router->getRoutes()->refreshNameLookups();
        url()->setRoutes($router->getRoutes());
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
