<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Nav;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * أربع بوابات في منتجين على مضيفين: بوابات الوزارة الثلاث على مضيفها
 * (gov_domain) — الإحصاء تحت /stats ومعه شاشة العرض تحت /screens، والخدمات
 * والتراخيص تحت /services، وإدارة النظام تحت /subadmin مع بوابة المعلومات — ثم
 * تطبيق حوات على النطاق الرئيسي تحت /admin. هذه الاختبارات تحرس الحدّ بينها:
 * أن كل صفحة تُقدَّم من مضيفها وموضعها فقط، وأن المواضع القديمة تُحوَّل، وأن
 * القائمة الجانبية تتبدّل مع البوابة — وكلها ينكسر بصمت عند إضافة مسار في
 * المكان الخطأ.
 *
 * إدارة النظام وتطبيق حوات خلف الدخول، فصفحاتهما تُطلب هنا بمستخدم داخل.
 */
class PortalSplitTest extends TestCase
{
    use RefreshDatabase;

    private function asSuperAdmin(): static
    {
        return $this->actingAs(User::factory()->superAdmin()->create());
    }

    /** لوحات شاشة العرض — تبويب واحد في قائمة الإحصاء. */
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
    public function test_a_wall_screen_answers_under_the_screens_prefix(string $path): void
    {
        $this->get($this->govUrl($path))->assertOk();
    }

    /** المسارات التي تُقدَّم من قسم الإحصاء تحت /stats. */
    public static function statisticsPages(): array
    {
        return [
            // رئيسة القسم هي موجز الإدارة العليا.
            ['/stats'],
            ['/stats/national-indicators'],
            ['/stats/performance-compare'],
            ['/stats/field-statistics'],
            ['/stats/approved-catch'],
            ['/stats/statistics-officers'],
            ['/stats/catch-trace'],
            ['/stats/analytics'],
            ['/stats/ai-assistant'],
            ['/stats/reports'],
            ['/stats/monthly-reports'],
            ['/stats/annual-bulletin'],
            ['/stats/markets'],
            ['/stats/supply-chain'],
            ['/stats/food-security'],
        ];
    }

    #[DataProvider('statisticsPages')]
    public function test_a_statistics_page_answers_under_the_stats_prefix(string $path): void
    {
        $this->get($this->govUrl($path))->assertOk();
    }

    /**
     * صفحات مركز العمليات — قسم المدير العام في تطبيق حوات تحت /admin. البيانات
     * الأساسية خرجت من قائمته إلى إدارة النظام، وصفحاتها باقية على مساراتها.
     */
    public static function opsPages(): array
    {
        return [
            ['/admin/governorates'],
            ['/admin/regions'],
            ['/admin/species'],
            ['/admin/fishing-seasons'],
            ['/admin/boats'],
            ['/admin/fishers'],
            ['/admin/trips'],
            ['/admin/boat-timeline'],
            ['/admin/ports'],
            ['/admin/fishing-sites'],
            ['/admin/discrepancy-review'],
            ['/admin/bycatch'],
        ];
    }

    #[DataProvider('opsPages')]
    public function test_an_operations_page_answers_under_the_admin_prefix(string $path): void
    {
        $this->asSuperAdmin()->get($this->mainUrl($path))->assertOk();
    }

    #[DataProvider('opsPages')]
    public function test_an_operations_page_is_behind_the_panel_login(string $path): void
    {
        $this->get($this->mainUrl($path))->assertRedirect(route('panel.login'));
    }

    /** لوحات إدارة النظام تحت /subadmin. */
    public static function subAdministrationPages(): array
    {
        return [
            ['/subadmin/org-structure'],
            ['/subadmin/staff-management'],
            ['/subadmin/audit-log'],
            ['/subadmin/admin-tasks'],
            ['/subadmin/staff-notifications'],
            ['/subadmin/alerts'],
            ['/subadmin/settings'],
        ];
    }

    #[DataProvider('subAdministrationPages')]
    public function test_a_system_administration_page_answers_under_its_prefix(string $path): void
    {
        $this->actingAs(User::factory()->create())->get($this->govUrl($path))->assertOk();
    }

    #[DataProvider('subAdministrationPages')]
    public function test_a_system_administration_page_is_behind_the_info_portal_login(string $path): void
    {
        // دخول واحد لنصفَي إدارة النظام على مضيف الوزارة، لا دخول التطبيق.
        $this->get($this->govUrl($path))->assertRedirect($this->govUrl('/login'));
    }

    /** المسارات التي تُقدَّم من قسم الخدمات والتراخيص تحت /services. */
    public static function servicesPages(): array
    {
        return [
            // رئيسة القسم هي خدمات الصيادين.
            ['/services'],
            ['/services/my-workspace'],
            ['/services/staff-dashboard'],
            ['/services/season-licenses'],
            ['/services/compliance'],
            ['/services/support'],
        ];
    }

    #[DataProvider('servicesPages')]
    public function test_a_services_page_answers_under_its_prefix(string $path): void
    {
        $this->get($this->govUrl($path))->assertOk();
    }

    /** المواضع التي كانت تُقدَّم منها هذه الصفحات قبل نقلها تحت بادئات. */
    public static function vacatedPaths(): array
    {
        return [
            ['/production'], ['/sea-map'], ['/compliance'], ['/alerts'], ['/national-indicators'], ['/reports'],
            ['/governorates'], ['/boats'], ['/ports'], ['/markets'], ['/settings'],
        ];
    }

    #[DataProvider('vacatedPaths')]
    public function test_a_moved_page_no_longer_answers_at_its_old_path(string $path): void
    {
        $this->get($this->mainUrl($path))->assertNotFound();
        $this->get($this->govUrl($path))->assertNotFound();
    }

    /** مواضع اللوحات على مضيف الوزارة قبل نقلها، ووجهة كل منها. */
    public static function sectionRedirects(): array
    {
        return [
            ['/gov/statistics', '/stats'],
            ['/gov/executive-briefing', '/stats'],
            ['/stats/executive-briefing', '/stats'],
            ['/gov/national-indicators', '/stats/national-indicators'],
            ['/gov/annual-bulletin', '/stats/annual-bulletin'],
            ['/gov/alerts', '/subadmin/alerts'],
            ['/services/fisher-services', '/services'],
            ['/gov/compliance', '/services/compliance'],
            ['/services/staff-management', '/subadmin/staff-management'],
            // لوحات القاعة نفسها انتقلت من /gov إلى /screens.
            ['/gov', '/screens'],
            ['/gov/overview', '/screens/overview'],
            ['/gov/sea-map', '/screens/sea-map'],
        ];
    }

    #[DataProvider('sectionRedirects')]
    public function test_a_moved_page_redirects_from_where_it_used_to_live(string $old, string $new): void
    {
        // الروابط المحفوظة قبل النقل تبقى عاملة، ولا تُقدَّم الصفحة من موضعين.
        $this->get($this->govUrl($old))->assertMovedPermanently()->assertRedirect($this->govUrl($new));
    }

    public function test_the_old_users_page_redirects_to_the_one_that_edits_them(): void
    {
        // صفحة /subadmin/users كانت نسخة للعرض فقط من تبويب بوابة المعلومات.
        $this->get($this->govUrl('/subadmin/users'))->assertMovedPermanently()->assertRedirect(route('admin.tab', 'permissions'));
    }

    /** ما كان من بوابات الوزارة على النطاق الرئيسي، ووجهته على مضيفها. */
    public static function mainHostLeftovers(): array
    {
        return [
            ['/sections', '/'],
            ['/login', '/login'],
            ['/stats', '/stats'],
            ['/stats/reports?year=2026', '/stats/reports?year=2026'],
            ['/services/support', '/services/support'],
            ['/subadmin/geo', '/subadmin/geo'],
            ['/gov/sea-map', '/gov/sea-map'],
        ];
    }

    #[DataProvider('mainHostLeftovers')]
    public function test_a_ministry_page_on_the_main_domain_moves_to_the_ministry_host(string $old, string $new): void
    {
        $this->get($this->mainUrl($old))->assertMovedPermanently()->assertRedirect($this->govUrl($new));
    }

    public function test_each_host_serves_only_its_own_product(): void
    {
        // جذر النطاق الرئيسي صفحة الهبوط، وجذر مضيف الوزارة صناديق بواباتها.
        $this->get($this->mainUrl('/'))->assertOk()->assertSee(route('landing.register'), false);
        $this->get($this->govUrl('/'))->assertOk()->assertDontSee(route('landing.register'), false);

        // تطبيق حوات وصفحاته العامة لا تُقدَّم من مضيف الوزارة.
        foreach (['/admin', '/admin/login', '/counter/apply', '/api/docs'] as $path) {
            $this->get($this->govUrl($path))->assertNotFound();
        }
    }

    public function test_another_host_is_sent_to_the_main_domain(): void
    {
        // www. أو عنوان الخادم: المسار نفسه على النطاق الرئيسي.
        $this->get('http://www.'.config('hawat.domain').'/admin/login?x=1')
            ->assertMovedPermanently()
            ->assertRedirect($this->mainUrl('/admin/login?x=1'));
    }

    public function test_the_ministry_host_offers_its_three_portals_and_not_the_app(): void
    {
        $this->get($this->govUrl('/'))
            ->assertOk()
            ->assertSeeInOrder(['الإحصاء', 'الخدمات والتراخيص', 'إدارة النظام'], false)
            ->assertSee('href="'.route('stats.executive-briefing').'"', false)
            ->assertSee('href="'.route('services.fisher-services').'"', false)
            ->assertSee('href="'.route('admin.index').'"', false)
            // تطبيق حوات على النطاق الرئيسي، فلا صندوق له هنا.
            ->assertDontSee('تطبيق حوات', false)
            ->assertDontSee(route('panel.home'), false)
            ->assertDontSee('href="'.route('screens.home').'"', false);
    }

    public function test_each_portal_renders_its_own_sidebar(): void
    {
        // كل صفحة ترى روابط بوابتها ولا ترى روابط غيرها. شاشة العرض من الإحصاء،
        // وتُفتح على وضع العرض بلا قائمة جانبية، فنطلبها بتخطيطها الكامل.
        $this->get($this->govUrl('/screens/production?screen=0'))
            ->assertSee(route('stats.field-statistics'), false)
            ->assertDontSee(route('trips'), false)
            ->assertDontSee(route('services.support'), false);

        $this->get($this->govUrl('/stats/field-statistics'))
            ->assertSee(route('stats.reports'), false)
            ->assertSee(route('screens.home'), false)
            ->assertDontSee(route('trips'), false);

        $this->get($this->govUrl('/services'))
            ->assertSee(route('services.support'), false)
            ->assertDontSee(route('trips'), false)
            ->assertDontSee(route('stats.field-statistics'), false)
            ->assertDontSee(route('subadmin.staff-management'), false);

        $this->asSuperAdmin()->get($this->mainUrl('/admin/trips'))
            ->assertSee(route('bycatch'), false)
            ->assertSee(route('panel.users'), false)
            ->assertDontSee(route('screens.production'), false)
            ->assertDontSee(route('stats.field-statistics'), false);

        $this->get($this->govUrl('/subadmin/org-structure'))
            ->assertSee(route('subadmin.audit-log'), false)
            ->assertSee(route('subadmin.staff-management'), false)
            ->assertDontSee(route('trips'), false)
            ->assertDontSee(route('stats.field-statistics'), false);
    }

    public function test_master_data_is_left_to_system_administration(): void
    {
        // البيانات الأساسية موضعها إدارة النظام وحدها، فلا تعرضها قائمة المدير العام.
        $response = $this->asSuperAdmin()->get($this->mainUrl('/admin/trips'))->assertOk();

        foreach (['regions', 'governorates', 'species', 'fishing-seasons', 'boats', 'fishers', 'ports', 'fishing-sites'] as $route) {
            $response->assertDontSee('href="'.route($route).'"', false);
        }

        $this->get($this->govUrl('/subadmin/settings'))
            ->assertSee(route('admin.tab', 'geo'), false)
            ->assertSee(route('admin.tab', 'fleet'), false)
            ->assertSee(route('admin.tab', 'seasons'), false);
    }

    public function test_system_administration_shows_one_menu_for_both_halves(): void
    {
        // نصفا إدارة النظام قائمة واحدة: لوحات القسم في تبويبات بوابة المعلومات،
        // وتبويباتها في لوحات القسم.
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.tab', 'geo'))
            ->assertOk()
            ->assertSee(route('subadmin.org-structure'), false)
            ->assertSee(route('subadmin.audit-log'), false);

        $this->get(route('subadmin.alerts'))
            ->assertSee(route('admin.tab', 'permissions'), false)
            ->assertSee(route('admin.tab', 'powerbi'), false);
    }

    public function test_every_info_portal_tab_has_its_place_in_the_menu(): void
    {
        // القائمة الجانبية لبوابة المعلومات هي قائمة إدارة النظام، فتبويب لا موضع
        // له فيها لا يصل إليه أحد.
        $listed = collect(Nav::sections(Nav::SUBADMIN))
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('tab')
            ->filter()
            ->all();

        $this->assertEqualsCanonicalizing(array_keys(config('info.tabs')), $listed);
    }

    public function test_a_sections_tabs_are_gone_from_the_other_portals(): void
    {
        // اللوحة الواحدة في بوابة واحدة: لو عاد تبويب من تبويبات قسم إلى قائمة
        // بوابة أخرى لظهرت بادئته هنا.
        foreach (Nav::keys() as $portal) {
            foreach (Nav::sections($portal) as $section) {
                foreach ($section['items'] as $item) {
                    foreach ([Nav::STATS, Nav::SUBADMIN, Nav::SERVICES] as $owner) {
                        if ($portal === $owner) {
                            continue;
                        }

                        $this->assertStringStartsNotWith(
                            $owner.'.',
                            $item['route'],
                            "تبويب من قسم {$owner} ما زال في قائمة بوابة أخرى: {$item['route']}"
                        );
                    }
                }
            }
        }
    }

    public function test_the_sidebar_links_resolve_for_every_navigation_entry(): void
    {
        // رابط بمسار غير مسجَّل يرمي استثناءً عند العرض، فنتحقق منها جميعًا مقدمًا.
        foreach ([...Nav::keys(), 'screens'] as $portal) {
            $sections = $portal === 'screens' ? Nav::screens() : Nav::sections($portal);

            foreach ($sections as $section) {
                foreach ($section['items'] as $item) {
                    $this->assertTrue(
                        Route::has($item['route']),
                        "القائمة الجانبية تشير إلى مسار غير مسجَّل: {$item['route']}"
                    );
                }
            }
        }
    }
}
