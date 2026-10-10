<?php

namespace Tests\Feature;

use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\OnGovHost;
use Tests\TestCase;

/**
 * بوابة المعلومات نصف إدارة النظام تحت /subadmin، خلف تسجيل دخول لأنها تحرّر
 * البيانات الأساسية. هذه الاختبارات تحرس الباب المغلق أمام الزائر، وتعايش
 * تبويباتها مع لوحات القسم في البادئة نفسها، وتحرير الحقول المرتبطة بمفاتيح
 * أجنبية — وهي المواضع التي تنكسر بصمت عند تغيير المخطط.
 */
class InfoPortalTest extends TestCase
{
    use OnGovHost;
    use RefreshDatabase;

    private const PORTAL = '/subadmin';

    /**
     * البوابة كلها خلف الدخول، فكل اختبار يمسّ محتواها يبدأ بمستخدم داخلٍ إليها.
     */
    private function signIn(): User
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        return $user;
    }

    private function governorate(): Governorate
    {
        $region = Region::create(['name' => 'المنطقة الشرقية', 'code' => 'EST']);

        return Governorate::create([
            'region_id' => $region->id,
            'name' => 'القطيف',
            'code' => 'QTF',
        ]);
    }

    public function test_the_portal_answers_under_the_subadmin_prefix(): void
    {
        $this->signIn();

        // الرئيسة نظرة عامة تقود إلى كل تبويب، والتبويب يعرض جدوله.
        $this->get(self::PORTAL)->assertOk()->assertSee(route('admin.tab', 'geo'), false);
        $this->get(self::PORTAL.'/geo')->assertOk();
        $this->get(self::PORTAL.'/powerbi')->assertOk();
        $this->get(self::PORTAL.'/stats')->assertOk();
    }

    public function test_the_tabs_share_the_prefix_with_the_section_pages(): void
    {
        $this->signIn();

        $this->get(self::PORTAL.'/audit-log')->assertOk();
        $this->get(self::PORTAL.'/org-structure')->assertOk();
    }

    public function test_the_old_host_and_prefix_redirect_into_the_prefix(): void
    {
        // البوابة كانت تحت /info في النطاق الرئيسي، وقبلها على مضيفها info.
        $this->get($this->mainUrl('/info'))->assertMovedPermanently()->assertRedirect($this->govUrl('/subadmin'));
        $this->get($this->mainUrl('/info/admin/geo'))->assertMovedPermanently()->assertRedirect($this->govUrl('/subadmin/geo'));

        $oldHost = 'http://info.'.config('hawat.domain');

        $this->get($oldHost)->assertMovedPermanently()->assertRedirect($this->govUrl('/subadmin'));
        $this->get($oldHost.'/admin/geo')->assertMovedPermanently()->assertRedirect($this->govUrl('/subadmin/geo'));
        $this->get($oldHost.'/login')->assertMovedPermanently()->assertRedirect($this->govUrl('/login'));
    }

    public function test_a_search_narrows_the_records(): void
    {
        $this->signIn();
        $governorate = $this->governorate();

        Port::create(['name' => 'ميناء الدمام', 'governorate_id' => $governorate->id, 'status' => 'نشط']);
        Port::create(['name' => 'ميناء جازان', 'governorate_id' => $governorate->id, 'status' => 'نشط']);

        $this->get(self::PORTAL.'/geo?resource=ports&q=جازان')
            ->assertOk()
            ->assertSee('ميناء جازان')
            ->assertDontSee('ميناء الدمام');
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get(self::PORTAL)->assertRedirect(route('login'));
        $this->get(self::PORTAL.'/geo')->assertRedirect(route('login'));

        // والكتابة محجوبة كالقراءة: لا يكفي إخفاء الصفحة عن الزائر.
        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء زائر',
            'status' => 'نشط',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('ports', 0);
    }

    public function test_the_login_page_is_open_to_a_guest(): void
    {
        $this->get(route('login'))->assertOk()->assertSee(config('info.title'), false);
    }

    public function test_a_known_user_signs_in_and_out(): void
    {
        $user = User::factory()->create(['password' => 'كلمة-سر-الاختبار']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'كلمة-سر-الاختبار'])
            ->assertRedirect(route('admin.index'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_wrong_password_is_refused(): void
    {
        $user = User::factory()->create(['password' => 'كلمة-سر-الاختبار']);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'كلمة-أخرى'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_an_unknown_tab_is_not_found_rather_than_an_error(): void
    {
        $this->signIn();

        $this->get(self::PORTAL.'/no-such-tab')->assertNotFound();
    }

    public function test_a_record_is_created_through_a_relation_backed_select(): void
    {
        $this->signIn();
        $governorate = $this->governorate();

        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء القطيف',
            'code' => 'PQTF',
            'governorate_id' => $governorate->id,
            'status' => 'نشط',
        ])->assertRedirect();

        $this->assertDatabaseHas('ports', [
            'name' => 'ميناء القطيف',
            'governorate_id' => $governorate->id,
        ]);
    }

    public function test_a_relation_value_outside_the_option_list_is_rejected(): void
    {
        $this->signIn();
        $this->governorate();

        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء وهمي',
            'governorate_id' => 4321,
            'status' => 'نشط',
        ])->assertSessionHasErrors('governorate_id');

        $this->assertDatabaseCount('ports', 0);
    }

    public function test_a_status_outside_the_option_list_is_rejected(): void
    {
        $this->signIn();
        $governorate = $this->governorate();

        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء القطيف',
            'governorate_id' => $governorate->id,
            'status' => 'حالة غير معرّفة',
        ])->assertSessionHasErrors('status');
    }

    public function test_a_record_is_updated_and_deleted(): void
    {
        $this->signIn();
        $governorate = $this->governorate();
        $port = Port::create(['name' => 'ميناء دارين', 'governorate_id' => $governorate->id]);

        $this->put(route('admin.resource.update', ['tab' => 'geo', 'resource' => 'ports', 'id' => $port->id]), [
            'name' => 'ميناء دارين الجديد',
            'governorate_id' => $governorate->id,
            'status' => 'صيانة',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('ports', ['id' => $port->id, 'name' => 'ميناء دارين الجديد', 'status' => 'صيانة']);

        $this->delete(route('admin.resource.destroy', ['tab' => 'geo', 'resource' => 'ports', 'id' => $port->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('ports', ['id' => $port->id]);
    }

    public function test_every_write_lands_in_the_audit_log_under_its_author(): void
    {
        $user = $this->signIn();
        $governorate = $this->governorate();

        $this->post(route('admin.resource.store', ['tab' => 'geo', 'resource' => 'ports']), [
            'name' => 'ميناء الدمام',
            'governorate_id' => $governorate->id,
            'status' => 'نشط',
        ])->assertSessionHasNoErrors();

        // الدخول ليس حراسةً فحسب: هو ما يجعل للسجل صاحبًا معروفًا.
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'إنشاء',
            'entity' => 'Port',
            'user_email' => $user->email,
        ]);
    }

    public function test_a_resource_is_written_only_through_its_own_tab(): void
    {
        $this->signIn();
        $governorate = $this->governorate();

        // الموانئ من تبويب البيانات الجغرافية، فلا تُكتب من تبويب الأسطول.
        $this->post(route('admin.resource.store', ['tab' => 'fleet', 'resource' => 'ports']), [
            'name' => 'ميناء الدمام',
            'governorate_id' => $governorate->id,
            'status' => 'نشط',
        ])->assertNotFound();

        $this->assertDatabaseMissing('ports', ['name' => 'ميناء الدمام']);
    }

    public function test_a_dropped_tab_redirects_to_the_page_that_replaced_it(): void
    {
        // الرخص موضعها الخدمات والتراخيص، وسجل العمليات صفحته في إدارة النظام.
        $this->signIn();

        $this->get(self::PORTAL.'/licenses')
            ->assertMovedPermanently()
            ->assertRedirect(route('services.season-licenses'));

        $this->get(self::PORTAL.'/audit')
            ->assertMovedPermanently()
            ->assertRedirect(route('subadmin.audit-log'));
    }
}
