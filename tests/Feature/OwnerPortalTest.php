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
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee(route('panel.owner.boats'), false)
            ->assertDontSee(route('panel.users'), false);
    }

    public function test_owner_pages_are_for_owners_only(): void
    {
        $captain = User::factory()->role(Role::CAPTAIN)->ownedBy($this->owner)->create();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($captain)->get('/admin/owner/boats')->assertForbidden();
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

    public function test_the_owner_creates_a_captain_who_gets_a_fisher_record_and_can_log_in(): void
    {
        $boat = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id]);

        $this->asOwner()->post('/admin/owner/captains', [
            'name' => 'كابتن الاختبار',
            'phone' => '٠٥٥٩٩٩٨٨٧٧',
            'password' => 'captain-123',
            'national_id' => '1055443322',
            'boat_id' => $boat->id,
            'active' => 1,
        ])->assertRedirect(route('panel.owner.captains'));

        $captain = User::where('phone', '0559998877')->firstOrFail();
        $this->assertTrue($captain->hasAppRole(Role::CAPTAIN));
        $this->assertSame($this->owner->id, $captain->owner_id);
        $this->assertSame($boat->id, Fisher::where('user_id', $captain->id)->value('boat_id'));
        $this->assertSame($captain->id, $boat->fresh()->captain_id);

        $this->actingAs(User::factory()->superAdmin()->create())->get('/admin/fishers')
            ->assertOk()->assertSee('كابتن الاختبار');

        $this->asOwner()->get('/admin/owner/captains')->assertOk()->assertSee('0559998877');
    }

    public function test_the_owner_runs_a_trip_from_its_page_and_sells_the_counted_catch(): void
    {
        $captain = User::factory()->role(Role::CAPTAIN)->ownedBy($this->owner)->create();
        $boat = Boat::factory()->ownedBy($this->owner)->captainedBy($captain)->create(['port_id' => $this->port->id]);
        $species = Species::factory()->create(['name_ar' => 'الكنعد (اختبار)']);
        $customer = Customer::factory()->ofAccount($this->owner)->create(['name' => 'زبون الاختبار']);
        $dalal = User::factory()->role(Role::DALAL)->create(['name' => 'دلال الاختبار']);

        $response = $this->asOwner()->post('/admin/owner/trips', ['boat_id' => $boat->id, 'planned_days' => 2]);
        $trip = Trip::forOwner($this->owner)->firstOrFail();
        $response->assertRedirect(route('panel.owner.trips.show', $trip));
        $this->assertSame($captain->id, $trip->captain_id);

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

        foreach (['maintenance', 'crew', 'employees', 'customers', 'vendors'] as $page) {
            $this->asOwner()->get("/admin/owner/{$page}")->assertOk()->assertSee('recordFormEl');
        }

        $this->asOwner()->post('/admin/owner/customers', ['name' => 'عميل ويب', 'phone' => '0501112222'])
            ->assertRedirect(route('panel.owner.customers'));
        $this->assertDatabaseHas('customers', ['name' => 'عميل ويب', 'account_user_id' => $this->owner->id]);

        $this->asOwner()->post('/admin/owner/crew', ['name' => 'بحّار ويب', 'national_id' => '3000000001', 'port_id' => $this->port->id])
            ->assertRedirect(route('panel.owner.crew'));
        $this->assertDatabaseHas('fishers', ['name' => 'بحّار ويب', 'owner_id' => $this->owner->id, 'user_id' => null]);
    }
}
