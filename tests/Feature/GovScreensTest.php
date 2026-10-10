<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Nav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\OnGovHost;
use Tests\TestCase;

/**
 * شاشة العرض (/screens على مضيف الوزارة، تبويب في قائمة الإحصاء) تُعرض على شاشة قاعة: رئيستها شاشة
 * اختيار بمربّعات، ونقر المربّع يفتح لوحته في وضع العرض (?screen=1) بلا قائمة
 * جانبية ولا شريط علوي. هذه الاختبارات تحرس الطرفين — أن كل لوحة لها مربّعها،
 * وأن وضع العرض يطوي التخطيط.
 */
class GovScreensTest extends TestCase
{
    use OnGovHost;
    use RefreshDatabase;

    public function test_the_home_screen_offers_a_box_for_every_other_tab(): void
    {
        $response = $this->get('/screens')->assertOk();

        foreach (Nav::screens() as $section) {
            foreach ($section['items'] as $item) {
                if ($item['route'] === 'screens.home') {
                    // شاشة الاختيار لا تعرض مربّعًا لنفسها.
                    continue;
                }

                $response->assertSee($item['label'], false)
                    ->assertSee('href="'.route($item['route'], ['screen' => 1]).'"', false);
            }
        }
    }

    /** لوحات لوحة الحكومة التي تُفتح من مربّعاتها. */
    public static function screenPages(): array
    {
        return [
            ['/screens'],
            ['/screens/overview'],
            ['/screens/sea-map'],
            ['/screens/production'],
            ['/screens/ports-compare'],
            ['/screens/sustainability'],
        ];
    }

    #[DataProvider('screenPages')]
    public function test_a_government_page_opens_in_display_mode_on_its_own(string $url): void
    {
        // الشاشة الكبيرة لا أحد يضبطها عند كل تشغيل: العنوان المجرّد يكفي.
        $this->get($url)
            ->assertOk()
            ->assertSee('class="screen-bar"', false)
            ->assertDontSee('class="sidebar"', false)
            ->assertDontSee('class="topbar"', false);
    }

    #[DataProvider('screenPages')]
    public function test_the_full_layout_comes_back_when_display_mode_is_turned_off(string $url): void
    {
        $this->get($url.'?screen=0')
            ->assertOk()
            ->assertSee('class="sidebar"', false)
            ->assertSee('class="topbar"', false)
            ->assertDontSee('class="screen-bar"', false);
    }

    public function test_only_the_government_portal_opens_in_display_mode(): void
    {
        // بقية البوابات تُدار من مكتب لا من قاعة، فتبقى على تخطيطها الكامل.
        // إدارة النظام وتطبيق حوات خلف الدخول، فتُطلب صفحاتهما بمدير عام داخل.
        $this->actingAs(User::factory()->superAdmin()->create());

        // صفحة التطبيق على النطاق الرئيسي، فتُطلب بعنوانها المطلق.
        foreach (['/stats', '/subadmin/org-structure', '/services', route('boats')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('class="sidebar"', false)
                ->assertDontSee('class="screen-bar"', false);
        }
    }

    public function test_the_wall_screens_sit_under_one_tab_of_the_statistics_menu(): void
    {
        // خارج وضع العرض تظهر قائمة الإحصاء، وتبويب "شاشة العرض" نشطٌ على أي لوحة منها.
        $this->get('/screens/production?screen=0')
            ->assertOk()
            ->assertSee(route('stats.field-statistics'), false)
            ->assertSee('<a href="'.route('screens.home').'" class="nav-link is-active">', false);
    }

    public function test_display_mode_returns_to_the_home_screen_without_leaving_it(): void
    {
        // زر الرجوع داخل لوحة يبقى في وضع العرض، وعلى شاشة الاختيار وحدها يخرج منه.
        $this->get('/screens/production')
            ->assertSee('href="'.route('screens.home').'"', false);

        $this->get('/screens')
            ->assertSee('href="'.route('screens.home', ['screen' => 0]).'"', false);
    }
}
