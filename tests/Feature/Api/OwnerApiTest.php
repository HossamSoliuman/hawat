<?php

namespace Tests\Feature\Api;

use App\Models\Boat;
use App\Models\Customer;
use App\Models\Fisher;
use App\Models\Port;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Species;
use App\Models\StockMovement;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spectator\Spectator;
use Tests\TestCase;

/**
 * واجهة المالك /api/v1/owner/* — كل ردّ يُطابَق مع docs/api/openapi.yaml
 * (Spectator)، فلا تختلف الوثيقة التي يبني عليها مطوّر التطبيق عن الواقع.
 *
 * الرحلة تمرّ بدورتها كاملة: إنشاء → بدء → مخرجات الكابتن → إحصاء الوزارة
 * (صفحة الإحصاء الميداني نفسها) → بيع وإرسال للدلال حتى تنفد.
 */
class OwnerApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Port $port;

    private Species $hamour;

    private Species $shaari;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');

        $this->owner = User::factory()->owner()->create();
        $this->port = Port::factory()->create(['name' => 'ميناء القطيف (اختبار)']);
        $this->hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)']);
        $this->shaari = Species::factory()->create(['name_ar' => 'الشعري (اختبار)']);
    }

    private function asOwner(?User $user = null): static
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user ?? $this->owner);

        return $this;
    }

    private function boat(array $attributes = []): Boat
    {
        return Boat::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id] + $attributes);
    }

    public function test_owner_endpoints_refuse_other_roles(): void
    {
        $dalal = User::factory()->role(Role::DALAL)->create();

        $this->asOwner($dalal)->getJson('/api/v1/owner/dashboard')
            ->assertValidRequest()
            ->assertValidResponse(403);
    }

    public function test_lookups_come_in_one_response(): void
    {
        $this->asOwner()->getJson('/api/v1/lookups')
            ->assertValidRequest()
            ->assertValidResponse(200)
            ->assertJsonFragment(['name' => 'الهامور (اختبار)'])
            ->assertJsonPath('data.ports.0.name', 'ميناء القطيف (اختبار)')
            ->assertJsonCount(3, 'data.payment_methods');
    }

    public function test_owner_manages_boats_and_sees_only_their_own(): void
    {
        $stranger = Boat::factory()->ownedBy(User::factory()->owner()->create())->create();

        $created = $this->asOwner()->postJson('/api/v1/owner/boats', [
            'name' => 'نجم الخليج',
            'boat_number' => 'B-9001',
            'port_id' => $this->port->id,
            'length_m' => 12.5,
            'license_expiry' => '2027-01-01',
        ])->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.name', 'نجم الخليج')
            ->assertJsonPath('data.port.name', 'ميناء القطيف (اختبار)');

        $id = $created->json('data.id');
        $this->assertSame($this->owner->id, Boat::find($id)->owner_id);
        $this->assertSame($this->owner->name, Boat::find($id)->owner, 'العمود النصي القديم يُملأ من المالك');

        $this->asOwner()->getJson('/api/v1/owner/boats')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data');

        $this->asOwner()->putJson("/api/v1/owner/boats/{$id}", ['name' => 'نجم الخليج 2', 'boat_number' => 'B-9001', 'port_id' => $this->port->id, 'status' => 'صيانة'])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', 'صيانة');

        $this->asOwner()->getJson("/api/v1/owner/boats/{$stranger->id}")
            ->assertValidRequest()->assertValidResponse(404);

        Trip::factory()->onBoat(Boat::find($id))->create();
        $this->asOwner()->deleteJson("/api/v1/owner/boats/{$id}")
            ->assertValidRequest()->assertValidResponse(422);
    }

    public function test_owner_adds_a_captain_as_a_ministry_fisher_record_without_an_account(): void
    {
        $boat = $this->boat();

        $response = $this->asOwner()->postJson('/api/v1/owner/captains', [
            'name' => 'أحمد الشمري',
            'phone' => '+966 55 111 2233',
            'national_id' => '1098765432',
            'boat_id' => $boat->id,
            'nationality' => 'سعودي',
        ])->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.is_captain', true)
            ->assertJsonPath('data.phone', '0551112233')
            ->assertJsonPath('data.boat.id', $boat->id)
            ->assertJsonPath('data.port.id', $this->port->id)
            ->assertJsonPath('data.role.name', 'قبطان')
            ->assertJsonPath('data.status', 'نشط');

        $captain = Fisher::findOrFail($response->json('data.id'));
        $this->assertTrue($captain->is_captain);
        $this->assertSame($this->owner->id, $captain->owner_id);
        $this->assertSame($captain->id, $boat->fresh()->captain_id);
        $this->assertSame('أحمد الشمري', $boat->fresh()->captain);

        // لا حساب دخول للكابتن: الجوال ليس حسابًا في حوات.
        $this->assertDatabaseMissing('users', ['phone' => '0551112233']);

        // التعطيل بالحالة، والقائمة تصفّيه.
        $this->asOwner()->putJson("/api/v1/owner/captains/{$captain->id}", [
            'name' => 'أحمد الشمري', 'national_id' => '1098765432', 'boat_id' => $boat->id, 'status' => 'غير نشط', 'version' => 1,
        ])->assertValidRequest()->assertValidResponse(200)->assertJsonPath('data.status', 'غير نشط');

        $this->asOwner()->getJson('/api/v1/owner/captains?status=نشط')
            ->assertValidRequest()->assertValidResponse(200)->assertJsonCount(0, 'data');

        // كابتن مالك آخر 404، والكابتن لا يظهر في الطاقم.
        $other = User::factory()->owner()->create();
        $this->asOwner($other)->getJson("/api/v1/owner/captains/{$captain->id}")->assertValidRequest()->assertValidResponse(404);
        $this->asOwner()->getJson("/api/v1/owner/crew/{$captain->id}")->assertValidRequest()->assertValidResponse(404);
        $this->asOwner()->getJson('/api/v1/owner/crew')->assertValidRequest()->assertValidResponse(200)->assertJsonCount(0, 'data');
    }

    public function test_deleting_a_captain_frees_the_boat_and_keeps_the_name_on_past_trips(): void
    {
        $captain = Fisher::factory()->captain()->ownedBy($this->owner)->create(['name' => 'كابتن قديم', 'port_id' => $this->port->id]);
        $boat = $this->boat(['captain_id' => $captain->id]);
        $trip = Trip::factory()->onBoat($boat)->captainedBy($captain)->create(['status' => Trip::APPROVED]);

        $this->asOwner()->deleteJson("/api/v1/owner/captains/{$captain->id}")
            ->assertValidRequest()->assertValidResponse(200);

        $this->assertModelMissing($captain);
        $this->assertNull($boat->fresh()->captain_id);
        $this->assertNull($boat->fresh()->captain);
        $this->assertNull($trip->fresh()->captain_id);
        $this->assertSame('كابتن قديم', $trip->fresh()->captain_name);
    }

    public function test_one_captain_per_boat_and_one_boat_per_captain(): void
    {
        $first = Fisher::factory()->captain()->ownedBy($this->owner)->create(['port_id' => $this->port->id]);
        $second = Fisher::factory()->captain()->ownedBy($this->owner)->create(['port_id' => $this->port->id]);
        $boatA = $this->boat(['captain_id' => $first->id]);
        $boatB = $this->boat();

        $this->assertSame($boatA->id, $first->fresh()->boat_id);

        // الكابتن الثاني على القارب الأول من نموذج القارب: الأول ينزل عنه.
        $this->asOwner()->putJson("/api/v1/owner/boats/{$boatA->id}", [
            'name' => $boatA->name, 'boat_number' => $boatA->boat_number, 'port_id' => $this->port->id, 'captain_id' => $second->id, 'version' => $boatA->fresh()->version,
        ])->assertValidRequest()->assertValidResponse(200)->assertJsonPath('data.captain.id', $second->id);

        $this->assertSame($boatA->id, $second->fresh()->boat_id);
        $this->assertNull($first->fresh()->boat_id);

        // والكابتن الثاني إلى القارب الثاني من نموذج الكابتن: يُرفع عن الأول.
        $this->asOwner()->putJson("/api/v1/owner/captains/{$second->id}", [
            'name' => $second->name, 'national_id' => $second->national_id, 'boat_id' => $boatB->id, 'version' => $second->fresh()->version,
        ])->assertValidRequest()->assertValidResponse(200)->assertJsonPath('data.boat.id', $boatB->id);

        $this->assertNull($boatA->fresh()->captain_id);
        $this->assertSame($second->id, $boatB->fresh()->captain_id);

        // القارب لا يقبل كابتنًا ليس كابتنًا لهذا المالك.
        $crew = Fisher::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id]);
        $this->asOwner()->putJson("/api/v1/owner/boats/{$boatB->id}", [
            'name' => $boatB->name, 'boat_number' => $boatB->boat_number, 'port_id' => $this->port->id, 'captain_id' => $crew->id,
        ])->assertValidRequest()->assertValidResponse(422)->assertJsonValidationErrors('captain_id');
    }

    public function test_owner_manages_crew_customers_employees_and_vendors(): void
    {
        $boat = $this->boat();

        $this->asOwner()->postJson('/api/v1/owner/crew', ['name' => 'سالم', 'national_id' => '2000000001', 'boat_id' => $boat->id])
            ->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.port.id', $this->port->id);

        $this->asOwner()->postJson('/api/v1/owner/crew', ['name' => 'بلا ميناء', 'national_id' => '2000000002'])
            ->assertValidRequest()->assertValidResponse(422);

        $customer = $this->asOwner()->postJson('/api/v1/owner/customers', ['name' => 'سوق السمك', 'phone' => '0555000001'])
            ->assertValidRequest()->assertValidResponse(201)->json('data.id');

        $this->asOwner()->postJson('/api/v1/owner/employees', ['name' => 'محاسب'])
            ->assertValidRequest()->assertValidResponse(201);

        $this->asOwner()->postJson('/api/v1/owner/vendors', ['name' => 'مورد الثلج'])
            ->assertValidRequest()->assertValidResponse(201);

        $this->asOwner()->getJson('/api/v1/owner/customers')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.0.id', $customer)
            ->assertJsonPath('data.0.sales_count', 0);

        $this->asOwner()->deleteJson("/api/v1/owner/customers/{$customer}")
            ->assertValidRequest()->assertValidResponse(200);
        $this->assertDatabaseMissing('customers', ['id' => $customer]);
    }

    public function test_trip_runs_from_creation_to_sale_through_the_ministry_count(): void
    {
        $captain = Fisher::factory()->captain()->ownedBy($this->owner)->create(['name' => 'كابتن الرحلة', 'port_id' => $this->port->id]);
        $boat = $this->boat(['captain_id' => $captain->id]);
        $customer = Customer::factory()->ofAccount($this->owner)->create();
        $dalal = User::factory()->role(Role::DALAL)->create();

        // 1. الإنشاء والإسناد → مجدولة.
        $trip = $this->asOwner()->postJson('/api/v1/owner/trips', ['boat_id' => $boat->id, 'planned_days' => 3])
            ->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.status', Trip::SCHEDULED)
            ->assertJsonPath('data.captain.name', 'كابتن الرحلة')
            ->assertJsonPath('data.departure_port.id', $this->port->id)
            ->assertJsonPath('data.progress_step', 0)
            ->json('data');
        $this->assertMatchesRegularExpression('/^TR-\d{4}-0001$/', $trip['trip_number']);

        // 2. البدء → في البحر، والقارب معه.
        $this->asOwner()->postJson("/api/v1/owner/trips/{$trip['id']}/start")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', Trip::AT_SEA)
            ->assertJsonPath('data.progress_step', 1);
        $this->assertSame(Trip::AT_SEA, $boat->fresh()->status);

        // 3. المالك يرسل المخرجات → بانتظار الإحصاء، والمجموع في captain_input_kg.
        $this->asOwner()->postJson("/api/v1/owner/trips/{$trip['id']}/catch", ['items' => [
            ['species_id' => $this->hamour->id, 'weight_kg' => 100],
            ['species_id' => $this->shaari->id, 'weight_kg' => 40.5],
            ['species_id' => $this->hamour->id, 'weight_kg' => 20, 'notes' => 'صندوق إضافي'],
        ]])->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', Trip::AWAITING_COUNT)
            ->assertJsonPath('data.captain_input_kg', 160.5)
            ->assertJsonPath('data.can_sell', false)
            ->assertJsonCount(2, 'data.catch_records');
        $this->assertSame('نشط', $boat->fresh()->status);

        // لا بيع قبل العد.
        $this->asOwner()->postJson('/api/v1/owner/sales', ['trip_id' => $trip['id'], 'items' => [['species_id' => $this->hamour->id, 'weight_kg' => 10, 'price_per_kg' => 50]]])
            ->assertValidRequest()->assertValidResponse(422);

        // 4. الوزارة تسجّل الإحصاء من صفحتها → يُفتح البيع ويدخل المخزون.
        $this->app['auth']->forgetGuards();
        $this->post(route('stats.field-statistics.record', $trip['id']), ['actual_weight_kg' => 158, 'statistics_officer' => 'محمد العوامي'])
            ->assertRedirect(route('stats.field-statistics'));

        $model = Trip::find($trip['id']);
        $this->assertSame(Trip::AWAITING_APPROVAL, $model->status);
        $this->assertSame(Trip::SALE_OPEN, $model->sale_status);
        $this->assertEqualsWithDelta(-2.5, (float) $model->diff_kg, 0.001);
        $this->assertSame('محمد العوامي', $model->statistics_officer);
        $this->assertSame(2, StockMovement::forHolder($this->owner)->count());

        $this->asOwner()->getJson("/api/v1/owner/trips/{$trip['id']}")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.progress_step', 4)
            ->assertJsonPath('data.can_sell', true)
            ->assertJsonFragment(['species_id' => $this->hamour->id, 'available_kg' => 120]);

        // 5. بيع فوق المتاح مرفوض؛ بيع صحيح يخصم من الدفتر.
        $this->asOwner()->postJson('/api/v1/owner/sales', ['trip_id' => $trip['id'], 'items' => [['species_id' => $this->hamour->id, 'weight_kg' => 121, 'price_per_kg' => 50]]])
            ->assertValidRequest()->assertValidResponse(422)
            ->assertJsonValidationErrorFor('items.0.weight_kg');

        $sale = $this->asOwner()->postJson('/api/v1/owner/sales', [
            'trip_id' => $trip['id'],
            'customer_id' => $customer->id,
            'discount' => 100,
            'items' => [
                ['species_id' => $this->hamour->id, 'weight_kg' => 100, 'price_per_kg' => 50],
                ['species_id' => $this->shaari->id, 'weight_kg' => 10.5, 'price_per_kg' => 30],
            ],
        ])->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.subtotal', 5315)
            ->assertJsonPath('data.total', 5215)
            ->assertJsonPath('data.remaining', 0)
            ->assertJsonPath('data.payment_status.name', 'مدفوع')
            ->assertJsonPath('data.customer.id', $customer->id)
            ->json('data');
        $this->assertMatchesRegularExpression('/^\d{2}-\d{2}-000001$/', $sale['invoice_number']);
        $this->assertSame(Trip::SALE_OPEN, $model->fresh()->sale_status);

        // 6. الباقي يُرسل للدلال → الرحلة مباعة ومخزون الدلال يحمل الوزن.
        $this->asOwner()->postJson('/api/v1/owner/consignments', ['trip_id' => $trip['id'], 'dalal_id' => $dalal->id, 'items' => [
            ['species_id' => $this->hamour->id, 'weight_kg' => 20],
            ['species_id' => $this->shaari->id, 'weight_kg' => 30],
        ]])->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.total_kg', 50)
            ->assertJsonPath('data.dalal.id', $dalal->id);

        $this->assertSame(Trip::SALE_DONE, $model->fresh()->sale_status);
        $this->assertEqualsWithDelta(20, (float) StockMovement::forHolder($dalal)->where('species_id', $this->hamour->id)->sum('weight_kg'), 0.001);
        $this->assertEqualsWithDelta(0, (float) StockMovement::forHolder($this->owner)->sum('weight_kg'), 0.001);

        $this->asOwner()->getJson('/api/v1/owner/sales')->assertValidRequest()->assertValidResponse(200)->assertJsonCount(1, 'data');
        $this->asOwner()->getJson("/api/v1/owner/sales/{$sale['id']}")->assertValidRequest()->assertValidResponse(200)->assertJsonCount(2, 'data.items');
        $this->asOwner()->getJson('/api/v1/owner/consignments')->assertValidRequest()->assertValidResponse(200)->assertJsonCount(1, 'data');
        $this->asOwner()->getJson('/api/v1/owner/stock/movements')->assertValidRequest()->assertValidResponse(200)->assertJsonCount(6, 'data');
        $this->asOwner()->getJson('/api/v1/owner/dalals')->assertValidRequest()->assertValidResponse(200)->assertJsonPath('data.0.id', $dalal->id);

        $this->asOwner()->getJson('/api/v1/owner/dashboard')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.kpis.revenue', 5215)
            ->assertJsonPath('data.kpis.catch_kg', 158)
            ->assertJsonPath('data.kpis.sales_count', 1);
    }

    public function test_dashboard_sums_the_chosen_period_and_lists_what_waits_for_the_owner(): void
    {
        $boat = $this->boat();
        Trip::factory()->onBoat($boat)->count(2)->create();
        Trip::factory()->onBoat($boat)->readyForSale()->create(['actual_weight_kg' => 80]);
        Trip::factory()->onBoat($boat)->readyForSale()->create(['actual_weight_kg' => 50, 'counted_at' => now()->subYears(2)]);

        $sale = fn (array $attributes) => Sale::factory()->create(['seller_id' => $this->owner->id] + $attributes);
        $sale(['total' => 1000, 'paid_amount' => 1000, 'sold_at' => now()]);
        $sale(['total' => 600, 'paid_amount' => 200, 'sold_at' => now()]);
        $sale(['total' => 900, 'paid_amount' => 0, 'sold_at' => now()->subYears(2)]);

        $this->asOwner()->getJson('/api/v1/owner/dashboard?period=month')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.period', 'month')
            ->assertJsonPath('data.kpis.revenue', 1600)
            ->assertJsonPath('data.kpis.sales_count', 2)
            ->assertJsonPath('data.kpis.catch_kg', 80)
            // غير المحصّل دين قائم: فاتورة قبل سنتين ما زالت مستحقة هذا الشهر.
            ->assertJsonPath('data.kpis.unpaid', 1300)
            ->assertJsonPath('data.action_items.trips_to_start', 2)
            ->assertJsonPath('data.action_items.trips_to_sell', 2)
            ->assertJsonPath('data.action_items.unpaid_sales', 2)
            ->assertJsonPath('data.action_items.unpaid_total', 1300);

        $this->asOwner()->getJson('/api/v1/owner/dashboard')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.period', 'all')
            ->assertJsonPath('data.kpis.revenue', 2500)
            ->assertJsonPath('data.kpis.catch_kg', 130);

        $this->asOwner()->getJson('/api/v1/owner/dashboard?period=decade')
            ->assertInvalidRequest()->assertValidResponse(422);

        $this->asOwner()->getJson('/api/v1/owner/sales?unpaid=1')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_a_trip_is_cancelled_with_a_reason_only_before_its_catch_is_submitted(): void
    {
        $boat = $this->boat();
        $trip = Trip::factory()->onBoat($boat)->atSea()->create();

        $this->asOwner()->postJson("/api/v1/owner/trips/{$trip->id}/cancel", [])
            ->assertInvalidRequest()->assertValidResponse(422);

        $this->asOwner()->postJson("/api/v1/owner/trips/{$trip->id}/cancel", ['reason' => 'عطل في المحرك'])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', Trip::CANCELLED)
            ->assertJsonPath('data.cancel_reason', 'عطل في المحرك');
        $this->assertSame('نشط', $boat->fresh()->status);

        $counted = Trip::factory()->onBoat($boat)->readyForSale()->create();
        $this->asOwner()->postJson("/api/v1/owner/trips/{$counted->id}/cancel", ['reason' => 'متأخر'])
            ->assertValidRequest()->assertValidResponse(422);

        $this->asOwner()->getJson('/api/v1/owner/trips?active=1')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $counted->id);
    }

    public function test_trip_stages_split_the_list_without_overlap(): void
    {
        $boat = $this->boat();
        $scheduled = Trip::factory()->onBoat($boat)->create();
        $atSea = Trip::factory()->onBoat($boat)->atSea()->create();
        $counting = Trip::factory()->onBoat($boat)->create(['status' => Trip::COUNTING]);
        $forSale = Trip::factory()->onBoat($boat)->readyForSale()->create();
        $sold = Trip::factory()->onBoat($boat)->readyForSale()->create(['sale_status' => Trip::SALE_DONE]);
        $cancelled = Trip::factory()->onBoat($boat)->create(['status' => Trip::CANCELLED]);

        $ids = fn (string $stage) => collect($this->asOwner()->getJson("/api/v1/owner/trips?stage={$stage}")
            ->assertValidRequest()->assertValidResponse(200)->json('data'))->pluck('id')->sort()->values()->all();

        $this->assertSame([$scheduled->id], $ids('scheduled'));
        $this->assertSame([$atSea->id, $counting->id], $ids('in_progress'));
        $this->assertSame([$forSale->id], $ids('for_sale'));
        $this->assertSame([$sold->id], $ids('sold'));
        $this->assertSame([$cancelled->id], $ids('cancelled'));

        $this->asOwner()->getJson('/api/v1/owner/trips?stage=everything')
            ->assertInvalidRequest()->assertValidResponse(422);
    }
}
