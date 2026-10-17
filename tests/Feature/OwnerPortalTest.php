<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\Customer;
use App\Models\Fisher;
use App\Models\Port;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Species;
use App\Models\Trip;
use App\Models\User;
use App\Services\Sms\ArraySmsSender;
use App\Services\Sms\SmsSender;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * بوابة المالك في /admin/owner/*: صفحاتها لدور المالك وحده، وما يُنشأ فيها
 * هو سجل الوزارة نفسه فيظهر في مركز المعلومات، والرحلة تُدار من صفحتها
 * حتى البيع.
 */
class OwnerPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Port $port;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        $this->owner = User::factory()->owner()->create(['name' => 'مالك الاختبار']);
        $this->port = Port::factory()->create(['name' => 'ميناء القطيف (اختبار)']);
    }

    private function asOwner(): static
    {
        return $this->actingAs($this->owner);
    }

    public function test_owner_home_shows_the_dashboard_and_the_owner_sidebar(): void
    {
        Boat::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id, 'name' => 'قارب الرئيسة']);

        $this->asOwner()->get('/admin')
            ->assertOk()
            ->assertSee('مرحبًا مالك الاختبار')
            ->assertSee('الأسماك المتوفرة')
            ->assertSee(route('panel.owner.trips'), false)
            ->assertSee(route('panel.owner.settings'), false)
            ->assertDontSee(route('panel.users'), false);
    }

    public function test_owner_sidebar_groups_collapse_with_only_the_current_group_open(): void
    {
        $html = $this->asOwner()->get('/admin/owner/expenses')->assertOk()->getContent();

        $this->assertSame(['المالية'], $this->openSidebarGroups($html));
        $this->assertStringContainsString('data-group="الدلالون"', $html);
        $this->assertStringContainsString('<summary class="nav-section-title">', $html);

        // الرئيسية والرحلات والمبيعات ثابتة فوق الأقسام المطويّة، بلا عنوان ولا طيّ.
        $home = $this->asOwner()->get('/admin')->assertOk()->getContent();
        $this->assertSame([], $this->openSidebarGroups($home));
        $this->assertMatchesRegularExpression('/<div class="nav-section is-pinned"\s*>\s*<a href="'.preg_quote(route('panel.home'), '/').'" class="nav-link is-active">/u', $home);
        $this->assertLessThan(strpos($home, '<details'), strpos($home, 'href="'.route('panel.owner.sales').'"'));

        // بقية الأدوار قوائمها قصيرة فلا تُطوى.
        $dalal = User::factory()->role(Role::DALAL)->create();
        $this->actingAs($dalal)->get('/admin')->assertOk()->assertDontSee('<details', false);
    }

    public function test_settings_opens_on_its_first_tab_and_every_master_data_page_is_a_tab(): void
    {
        $this->asOwner()->get('/admin/owner/settings')->assertRedirect(route('panel.owner.boats'));

        $tabs = config('hawat.owner_settings');

        foreach ($tabs as $tab) {
            $response = $this->asOwner()->get(route($tab['route']))
                ->assertOk()
                ->assertSee('<title>الإعدادات — '.$tab['label'], false)
                ->assertSee('href="'.route($tab['route']).'" class="is-active"', false);

            foreach ($tabs as $other) {
                $response->assertSee('href="'.route($other['route']).'"', false);
            }

            // الإعدادات نشطة في القائمة على كل تبويب، وقسمها وحده مفتوح.
            $this->assertSame(['الإعدادات والحساب'], $this->openSidebarGroups($response->getContent()));
        }
    }

    /**
     * أقسام القائمة المفتوحة من الخادم بأسمائها.
     *
     * @return array<int, string>
     */
    private function openSidebarGroups(string $html): array
    {
        preg_match_all('/<details[^>]*data-group="([^"]+)"[^>]*\sopen[\s>]/u', $html, $matches);

        return $matches[1];
    }

    public function test_owner_pages_are_for_owners_only(): void
    {
        $dalal = User::factory()->role(Role::DALAL)->create();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($dalal)->get('/admin/owner/boats')->assertForbidden();
        $this->actingAs($admin)->get('/admin/owner/boats')->assertForbidden();
        $this->asOwner()->get('/admin/owner/boats')->assertOk();
        $this->asOwner()->get('/admin/users')->assertForbidden();
    }

    public function test_a_boat_added_by_the_owner_appears_in_the_ministry_console(): void
    {
        $this->asOwner()->post('/admin/owner/boats', [
            'name' => 'فجر البحر (اختبار)',
            'boat_number' => 'B-7001',
            'port_id' => $this->port->id,
            'status' => 'نشط',
        ])->assertRedirect(route('panel.owner.boats'))->assertSessionHas('status');

        $boat = Boat::where('boat_number', 'B-7001')->firstOrFail();
        $this->assertSame($this->owner->id, $boat->owner_id);
        $this->assertSame('مالك الاختبار', $boat->owner);

        $this->actingAs(User::factory()->superAdmin()->create())->get('/admin/boats')
            ->assertOk()->assertSee('فجر البحر (اختبار)');

        // مالك آخر لا يرى القارب ولا يعدّله.
        $other = User::factory()->owner()->create();
        $this->actingAs($other)->put("/admin/owner/boats/{$boat->id}", ['name' => 'x', 'boat_number' => 'B-7001', 'port_id' => $this->port->id])
            ->assertNotFound();
    }

    public function test_the_owner_adds_a_captain_record_that_appears_in_the_ministry_console(): void
    {
        Storage::fake('public');
        $sms = new ArraySmsSender;
        $this->app->instance(SmsSender::class, $sms);
        $boat = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id]);

        $this->asOwner()->post('/admin/owner/captains', [
            'name' => 'كابتن الاختبار',
            'phone' => '٠٥٥٩٩٩٨٨٧٧',
            'national_id' => '1055443322',
            'boat_id' => $boat->id,
            'iqama_issue_date' => '2025-01-01',
            'iqama_expiry_date' => '2026-01-01',
            'avatar' => UploadedFile::fake()->image('c.jpg', 300, 300),
        ])->assertRedirect(route('panel.owner.captains'))->assertSessionHasNoErrors();

        $captain = Fisher::captains()->where('national_id', '1055443322')->firstOrFail();
        $this->assertSame($this->owner->id, $captain->owner_id);
        $this->assertSame('0559998877', $captain->phone);
        $this->assertSame($boat->id, $captain->boat_id);
        $this->assertSame($captain->id, $boat->fresh()->captain_id);
        $this->assertSame('2026-01-01', $captain->iqama_expiry_date->toDateString());
        Storage::disk('public')->assertExists($captain->avatar_path);

        // لا حساب ولا دعوة.
        $this->assertDatabaseMissing('users', ['phone' => '0559998877']);
        $this->assertSame([], $sms->sent);

        $this->actingAs(User::factory()->superAdmin()->create())->get('/admin/fishers')
            ->assertOk()->assertSee('كابتن الاختبار');

        $this->asOwner()->get('/admin/owner/captains')->assertOk()->assertSee('0559998877')->assertSee($boat->name);

        // تعطيل ثم حذف.
        $this->asOwner()->post(route('panel.owner.captains.toggle', $captain))->assertRedirect();
        $this->assertSame('غير نشط', $captain->fresh()->status);
        $this->asOwner()->delete(route('panel.owner.captains.destroy', $captain))->assertRedirect(route('panel.owner.captains'));
        $this->assertModelMissing($captain);
        $this->assertNull($boat->fresh()->captain_id);

        // كابتن مالك آخر 404.
        $foreign = Fisher::factory()->captain()->ownedBy(User::factory()->owner()->create())->create(['port_id' => $this->port->id]);
        $this->asOwner()->post(route('panel.owner.captains.toggle', $foreign))->assertNotFound();
    }

    public function test_the_owner_reads_the_catch_log_by_captain_and_boat(): void
    {
        $captain = Fisher::factory()->captain()->ownedBy($this->owner)->create(['name' => 'كابتن السجل', 'port_id' => $this->port->id]);
        $boat = Boat::factory()->ownedBy($this->owner)->captainedBy($captain)->create(['port_id' => $this->port->id]);
        $other = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id]);
        $species = Species::factory()->create(['name_ar' => 'الهامور (سجل)']);

        foreach ([[$boat, 'TR-2026-0901', 40], [$other, 'TR-2026-0902', 15]] as [$on, $number, $kg]) {
            $trip = Trip::factory()->onBoat($on)->create(['trip_number' => $number, 'status' => Trip::AWAITING_COUNT]);
            $trip->catchRecords()->create(['species_id' => $species->id, 'quantity_kg' => $kg, 'captain_kg' => $kg, 'recorded_at' => today()]);
        }

        $this->asOwner()->get(route('panel.owner.catch-log'))->assertOk()->assertSee('TR-2026-0901')->assertSee('TR-2026-0902')->assertSee('55.0');
        $this->asOwner()->get(route('panel.owner.catch-log', ['captain' => $captain->id]))->assertOk()
            ->assertSee('TR-2026-0901')->assertDontSee('TR-2026-0902')->assertSee('كابتن السجل');
        $this->asOwner()->get(route('panel.owner.catch-log', ['boat' => $other->id]))->assertOk()
            ->assertSee('TR-2026-0902')->assertDontSee('TR-2026-0901');

        // سجل مالك آخر لا يظهر.
        $this->actingAs(User::factory()->owner()->create())->get(route('panel.owner.catch-log'))->assertOk()->assertDontSee('TR-2026-0901');
    }

    public function test_the_web_boat_form_guards_stale_edits_and_shows_setup(): void
    {
        $boat = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id, 'boat_number' => 'B-8100', 'crew_count' => 0]);
        $form = ['name' => 'بعد التعديل', 'boat_number' => 'B-8100', 'port_id' => $this->port->id];

        $this->asOwner()->get('/admin/owner/boats')->assertOk()->assertSee('ناقص')->assertSee('لم يُسند إليه كابتن');

        $this->asOwner()->put("/admin/owner/boats/{$boat->id}", $form + ['version' => 1])->assertSessionHasNoErrors();
        $this->asOwner()->from('/admin/owner/boats')->put("/admin/owner/boats/{$boat->id}", ['name' => 'متأخر'] + $form + ['version' => 1])
            ->assertRedirect('/admin/owner/boats')->assertSessionHasErrors('version');
        $this->assertSame('بعد التعديل', $boat->fresh()->name);
    }

    public function test_the_owner_runs_a_trip_from_its_page_and_sells_the_counted_catch(): void
    {
        $captain = Fisher::factory()->captain()->ownedBy($this->owner)->create(['port_id' => $this->port->id]);
        $boat = Boat::factory()->ownedBy($this->owner)->captainedBy($captain)->create(['port_id' => $this->port->id]);
        $species = Species::factory()->create(['name_ar' => 'الكنعد (اختبار)']);
        $customer = Customer::factory()->ofAccount($this->owner)->create(['name' => 'زبون الاختبار']);
        $dalal = User::factory()->role(Role::DALAL)->create(['name' => 'دلال الاختبار']);

        $response = $this->asOwner()->post('/admin/owner/trips', ['boat_id' => $boat->id, 'planned_days' => 2]);
        $trip = Trip::forOwner($this->owner)->firstOrFail();
        $response->assertRedirect(route('panel.owner.trips.show', $trip));
        $this->assertSame($captain->id, $trip->captain_id);

        // الرحلة بانتظار الانطلاق تظهر في الرئيسية بزر البدء.
        $this->asOwner()->get('/admin')->assertOk()->assertSee('بانتظار الانطلاق')->assertSee(route('panel.owner.trips.start', $trip), false);
        $this->asOwner()->get(route('panel.owner.trips.show', $trip))->assertOk()->assertSee('ابدأ الرحلة');

        $this->asOwner()->post(route('panel.owner.trips.start', $trip))->assertRedirect();
        $this->assertSame(Trip::AT_SEA, $trip->fresh()->status);

        $this->asOwner()->post(route('panel.owner.trips.catch', $trip), ['items' => [['species_id' => $species->id, 'weight_kg' => 80]]])
            ->assertRedirect(route('panel.owner.trips.show', $trip));
        $this->assertSame(Trip::AWAITING_COUNT, $trip->fresh()->status);

        // صفحة البيع مغلقة قبل العد.
        $this->asOwner()->get(route('panel.owner.sales.create', ['trip' => $trip->id]))->assertOk()->assertSee('غير متاح للبيع');

        // الوزارة تعدّ من الإحصاء الميداني.
        $this->post(route('stats.field-statistics.record', $trip->id), ['actual_weight_kg' => 78])->assertRedirect();
        $this->assertSame(Trip::SALE_OPEN, $trip->fresh()->sale_status);

        $this->asOwner()->get(route('panel.owner.trips.show', $trip))->assertOk()->assertSee('بيع مصيد لزبون')->assertSee('اكتمل العد وجاهزة للبيع');
        $this->asOwner()->get(route('panel.owner.sales.create', ['trip' => $trip->id]))->assertOk()->assertSee('زبون الاختبار');

        $this->asOwner()->post('/admin/owner/sales', [
            'trip_id' => $trip->id,
            'customer_id' => $customer->id,
            'items' => [['species_id' => $species->id, 'weight_kg' => 50, 'price_per_kg' => 40]],
        ])->assertRedirect();
        $sale = Sale::forSeller($this->owner)->firstOrFail();
        $this->assertEqualsWithDelta(2000, (float) $sale->total, 0.001);

        $this->asOwner()->get(route('panel.owner.sales.show', $sale))->assertOk()->assertSee($sale->invoice_number)->assertSee('الكنعد (اختبار)');

        // بيع فوق المتاح يُعاد بخطأ تحقق.
        $this->asOwner()->from(route('panel.owner.sales.create', ['trip' => $trip->id]))->post('/admin/owner/sales', [
            'trip_id' => $trip->id,
            'items' => [['species_id' => $species->id, 'weight_kg' => 31, 'price_per_kg' => 40]],
        ])->assertSessionHasErrors('items.0.weight_kg');

        $this->asOwner()->post('/admin/owner/consignments', [
            'trip_id' => $trip->id,
            'dalal_id' => $dalal->id,
            'items' => [['species_id' => $species->id, 'weight_kg' => 30]],
        ])->assertRedirect();
        $this->assertSame(Trip::SALE_DONE, $trip->fresh()->sale_status);

        $this->asOwner()->get('/admin/owner/consignments')->assertOk()->assertSee('دلال الاختبار');
        $this->asOwner()->get('/admin/owner/sales')->assertOk()->assertSee($sale->invoice_number);
        $this->asOwner()->get('/admin/owner/trips?view=for-sale')->assertOk()->assertDontSee($trip->trip_number);
        $this->asOwner()->get('/admin')->assertOk()->assertSee('2,000');
    }

    public function test_lookup_pages_render_with_their_forms(): void
    {
        Boat::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id]);

        foreach (['maintenance', 'captains', 'crew', 'employees', 'customers', 'vendors'] as $page) {
            $this->asOwner()->get("/admin/owner/{$page}")->assertOk()->assertSee('recordFormEl');
        }

        $this->asOwner()->post('/admin/owner/customers', ['name' => 'عميل ويب', 'phone' => '0501112222'])
            ->assertRedirect(route('panel.owner.customers'));
        $this->assertDatabaseHas('customers', ['name' => 'عميل ويب', 'account_user_id' => $this->owner->id]);

        $this->asOwner()->post('/admin/owner/crew', ['name' => 'بحّار ويب', 'national_id' => '3000000001', 'port_id' => $this->port->id])
            ->assertRedirect(route('panel.owner.crew'));
        $this->assertDatabaseHas('fishers', ['name' => 'بحّار ويب', 'owner_id' => $this->owner->id, 'is_captain' => false]);
    }
}
