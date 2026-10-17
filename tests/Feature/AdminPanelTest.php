<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Species;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * لوحة الإدارة على /admin: باب واحد بالجوال أو البريد، ثم قائمة تتبدّل مع دور
 * التطبيق. هذه الاختبارات تحرس الباب (من يدخل ومن يُردّ)، وتفرّع القائمة على
 * الدور، وصفحة حسابات التطبيق التي ينشئ منها المدير العام بقية الأدوار.
 */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_a_guest_is_sent_to_the_panel_login_not_the_info_portal_login(): void
    {
        $this->get('/admin')->assertRedirect(route('panel.login'));
        $this->get('/admin/users')->assertRedirect(route('panel.login'));
        $this->get('/admin/boats')->assertRedirect(route('panel.login'));
    }

    public function test_an_app_account_signs_in_with_its_phone_and_a_super_admin_with_email(): void
    {
        $owner = User::factory()->owner()->create(['phone' => '0512345678', 'password' => 'secret-123']);
        $admin = $this->superAdmin();
        $admin->forceFill(['email' => 'boss@hawat.sa', 'password' => 'secret-123'])->save();

        // الجوال يُطبَّع: +966 والمسافات والأرقام العربية كلها تصل إلى الصيغة المخزّنة.
        $this->post(route('panel.login.store'), ['identifier' => '+966 51 234 5678', 'password' => 'secret-123'])
            ->assertRedirect(route('panel.home'));
        $this->assertAuthenticatedAs($owner);
        $this->assertNotNull($owner->fresh()->last_login_at);

        $this->post(route('panel.logout'))->assertRedirect(route('panel.login'));
        $this->assertGuest();

        $this->post(route('panel.login.store'), ['identifier' => 'BOSS@hawat.sa', 'password' => 'secret-123'])
            ->assertRedirect(route('panel.home'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_wrong_credentials_give_one_message_for_both_cases(): void
    {
        User::factory()->owner()->create(['phone' => '0512345678', 'password' => 'secret-123']);

        $this->from(route('panel.login'))
            ->post(route('panel.login.store'), ['identifier' => '0512345678', 'password' => 'nope'])
            ->assertRedirect(route('panel.login'))
            ->assertSessionHasErrors(['identifier' => 'بيانات الدخول غير صحيحة.']);

        $this->from(route('panel.login'))
            ->post(route('panel.login.store'), ['identifier' => '0599999999', 'password' => 'nope'])
            ->assertSessionHasErrors(['identifier' => 'بيانات الدخول غير صحيحة.']);

        $this->assertGuest();
    }

    public function test_a_ministry_user_without_an_app_role_is_refused_at_the_door(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertForbidden();
        $this->get('/admin/boats')->assertForbidden();
    }

    public function test_a_deactivated_account_is_logged_out_and_told_why(): void
    {
        $this->actingAs(User::factory()->owner()->inactive()->create());

        $this->get('/admin')
            ->assertRedirect(route('panel.login'))
            ->assertSessionHasErrors(['identifier' => 'هذا الحساب معطّل.']);

        $this->assertGuest();
    }

    public function test_the_sidebar_follows_the_role(): void
    {
        $this->actingAs($this->superAdmin())
            ->get('/admin')
            ->assertOk()
            ->assertSee(route('panel.users'), false)
            ->assertSee(route('trips'), false)
            ->assertSee('class="sidebar"', false)
            ->assertSee(route('panel.logout'), false);

        $this->actingAs(User::factory()->owner()->create(['name' => 'سالم المالك']))
            ->get('/admin')
            ->assertOk()
            ->assertSee('سالم المالك')
            ->assertSee('مالك القارب')
            ->assertDontSee(route('panel.users'), false)
            ->assertDontSee(route('boats'), false);
    }

    public function test_only_the_super_admin_reaches_the_accounts_page_and_the_ops_console(): void
    {
        $this->actingAs(User::factory()->owner()->create());

        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/boats')->assertForbidden();
        $this->post('/admin/users', [])->assertForbidden();

        $this->actingAs($this->superAdmin());

        $this->get('/admin/users')->assertOk()->assertSee('حسابات التطبيق');
        $this->get('/admin/boats')->assertOk();
    }

    public function test_the_super_admin_creates_an_owner_account_that_can_sign_in_by_phone(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('panel.users.store'), [
            'name' => 'أبو فهد',
            'phone' => '٠٥٥٥٥٥٥٥٥٥',
            'email' => '',
            'role_id' => Role::key(Role::OWNER)->id,
            'password' => 'secret-123',
            'active' => 1,
        ])->assertRedirect(route('panel.users'))->assertSessionHas('status');

        $owner = User::where('phone', '0555555555')->firstOrFail();

        $this->assertSame(Role::OWNER, $owner->app_role_key);
        $this->assertNull($owner->email);
        $this->assertNull($owner->owner_id);
        $this->assertDatabaseHas('audit_logs', ['entity' => 'User', 'action' => 'إنشاء']);

        $this->post(route('panel.logout'));

        $this->post(route('panel.login.store'), ['identifier' => '0555555555', 'password' => 'secret-123'])
            ->assertRedirect(route('panel.home'));
        $this->assertAuthenticatedAs($owner);
    }

    public function test_an_owner_employee_must_belong_to_an_owner_and_a_duplicate_phone_is_refused(): void
    {
        $this->actingAs($this->superAdmin());
        $owner = User::factory()->owner()->create(['phone' => '0511111111']);

        $employee = [
            'name' => 'الموظف',
            'phone' => '0522222222',
            'role_id' => Role::key(Role::EMPLOYEE)->id,
            'password' => 'secret-123',
        ];

        $this->from(route('panel.users'))->post(route('panel.users.store'), $employee)
            ->assertRedirect(route('panel.users'))
            ->assertSessionHasErrors('owner_id');

        $this->post(route('panel.users.store'), $employee + ['owner_id' => $owner->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($owner->id, User::where('phone', '0522222222')->firstOrFail()->owner_id);

        // الجوال نفسه بصيغة أخرى هو الجوال نفسه.
        $this->from(route('panel.users'))->post(route('panel.users.store'), $employee + ['owner_id' => $owner->id, 'phone' => '+966522222222'])
            ->assertSessionHasErrors('phone');
    }

    public function test_the_captain_is_not_an_app_role(): void
    {
        $this->assertDatabaseMissing('roles', ['key' => 'captain']);
        $this->assertNotContains('captain', Role::OWNER_MANAGED);
    }

    public function test_updating_without_a_password_keeps_the_old_one_and_toggling_revokes_tokens(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin);

        $dalal = User::factory()->role(Role::DALAL)->create(['phone' => '0533333333', 'password' => 'secret-123']);
        $dalal->createToken('phone');

        $this->put(route('panel.users.update', $dalal), [
            'name' => 'الدلال الجديد',
            'phone' => '0533333333',
            'role_id' => $dalal->role_id,
            'password' => '',
            'active' => 1,
        ])->assertRedirect(route('panel.users'));

        $dalal->refresh();
        $this->assertSame('الدلال الجديد', $dalal->name);
        $this->assertTrue(password_verify('secret-123', $dalal->password));

        $this->post(route('panel.users.toggle', $dalal))->assertRedirect(route('panel.users'));
        $this->assertFalse($dalal->fresh()->active);
        $this->assertSame(0, $dalal->tokens()->count());

        // المدير لا يعطّل نفسه ولا يحذفها — وإلا أغلق الباب على اللوحة.
        $this->post(route('panel.users.toggle', $admin))->assertSessionHasErrors('toggle');
        $this->delete(route('panel.users.destroy', $admin))->assertSessionHasErrors('toggle');
        $this->assertTrue($admin->fresh()->active);
    }

    public function test_the_accounts_page_lists_only_app_accounts_and_filters_by_role(): void
    {
        $this->actingAs($this->superAdmin());
        User::factory()->create(['name' => 'موظف وزارة بلا دور']);
        User::factory()->owner()->create(['name' => 'مالك أول', 'phone' => '0511111111']);
        User::factory()->role(Role::DALAL)->create(['name' => 'دلال أول', 'phone' => '0522222222']);

        $this->get(route('panel.users'))
            ->assertOk()
            ->assertSee('مالك أول')
            ->assertSee('دلال أول')
            ->assertDontSee('موظف وزارة بلا دور');

        // اسم المالك يبقى في قائمة "يتبع المالك" داخل النموذج، فالجوال هو ما يغيب من الجدول.
        $this->get(route('panel.users', ['role' => Role::DALAL]))
            ->assertSee('0522222222')
            ->assertDontSee('0511111111');
    }

    public function test_the_super_admin_adds_a_species_from_the_species_page(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get(route('species'))->assertOk()->assertSee('إضافة نوع');

        $this->post(route('species.store'), [
            'name_ar' => 'الهامور (اختبار)',
            'code' => 905,
            'name_sci' => 'Epinephelus coioides',
            'category' => 'أسماك',
            'status' => 'مستقر',
            'review_status' => 'مقبول مبدئيًا',
        ])->assertRedirect();

        $this->assertDatabaseHas('species', ['name_ar' => 'الهامور (اختبار)', 'code' => 905, 'category' => 'أسماك']);

        // الاسم العربي والرمز فريدان، فالتكرار يُرَدّ بخطأ لا بسجل ثانٍ.
        $this->from(route('species'))
            ->post(route('species.store'), ['name_ar' => 'الهامور (اختبار)', 'category' => 'أسماك', 'status' => 'مستقر'])
            ->assertRedirect(route('species'))
            ->assertSessionHasErrors('name_ar');
        $this->assertSame(1, Species::where('name_ar', 'الهامور (اختبار)')->count());
    }

    public function test_the_api_docs_page_and_spec_are_served(): void
    {
        $this->get('/api/docs')->assertOk()->assertSee('swagger-ui', false);
        $this->get('/api/openapi.yaml')->assertOk()->assertSee('/auth/login');
    }
}
