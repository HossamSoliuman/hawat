<?php

namespace Tests\Feature\Api;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Customer;
use App\Models\DalalPartnership;
use App\Models\DalalWorkerType;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Species;
use App\Models\Trip;
use App\Models\User;
use App\Services\Sales\SaleService;
use App\Services\Trips\TripService;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spectator\Spectator;
use Tests\TestCase;

/**
 * واجهة الدلال /api/v1/dalal/* وطلب التعامل من جهة المالك — كل ردّ يُطابَق
 * مع docs/api/openapi.yaml (Spectator).
 *
 * الدلال يرى مخزونه مما أرسله الملاك، ويبيع منه بالسطور ويحصّل، ويدير
 * عملاءه، ويقبل طلبات الملاك أو يرفضها، ويدفع لهم مستحقهم، ويقرأ تقاريره،
 * ويحفظ ملفه التجاري.
 */
class DalalApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $dalal;

    private Species $hamour;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');

        $this->owner = User::factory()->owner()->create(['name' => 'مالك الواجهة']);
        $this->dalal = User::factory()->dalal()->create(['name' => 'دلال الواجهة']);
        $this->hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)', 'name_sci' => 'Epinephelus coioides']);
    }

    private function as(User $user): static
    {
        // fresh(): المصنع لا يكتب locale فيبقى null في الذاكرة بينما القاعدة تعطيه 'ar'.
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user->fresh());

        return $this;
    }

    private function consign(float $kg, string $tripNumber = 'TR-2026-1501'): Trip
    {
        $boat = Boat::factory()->ownedBy($this->owner)->create();
        $trip = Trip::factory()->onBoat($boat)->forOwner($this->owner)->create(['trip_number' => $tripNumber, 'status' => Trip::AWAITING_APPROVAL, 'counted_at' => now()]);
        CatchRecord::create(['trip_id' => $trip->id, 'species_id' => $this->hamour->id, 'quantity_kg' => 200, 'counted_kg' => 200, 'recorded_at' => now()->toDateString()]);

        app(TripService::class)->openForSale($trip->fresh());
        app(SaleService::class)->consign($this->owner, ['trip_id' => $trip->id, 'dalal_id' => $this->dalal->id, 'items' => [['species_id' => $this->hamour->id, 'weight_kg' => $kg]]]);

        return $trip;
    }

    public function test_dalal_endpoints_are_for_dalals_only_and_scoped_to_the_dalal(): void
    {
        $this->as($this->owner)->getJson('/api/v1/dalal/dashboard')->assertValidRequest()->assertValidResponse(403);
        $this->as(User::factory()->role(Role::COUNTER)->create())->getJson('/api/v1/dalal/stock')->assertValidRequest()->assertValidResponse(403);

        $other = User::factory()->dalal()->create();
        $theirSale = Sale::factory()->create(['seller_id' => $other->id]);
        $theirCustomer = Customer::factory()->create(['account_user_id' => $other->id]);
        $theirRequest = DalalPartnership::factory()->create(['dalal_id' => $other->id]);

        $this->as($this->dalal)->getJson("/api/v1/dalal/sales/{$theirSale->id}")->assertValidRequest()->assertValidResponse(404);
        $this->as($this->dalal)->getJson("/api/v1/dalal/customers/{$theirCustomer->id}")->assertValidRequest()->assertValidResponse(404);
        $this->as($this->dalal)->postJson("/api/v1/dalal/partnerships/{$theirRequest->id}/accept")->assertValidRequest()->assertValidResponse(404);
        $this->as($this->dalal)->postJson("/api/v1/dalal/owners/{$this->owner->id}/payouts", ['amount' => 1])->assertValidRequest()->assertValidResponse(404);
        $this->as($this->dalal)->getJson('/api/v1/dalal/reports/unknown')->assertNotFound();
    }

    public function test_owner_requests_a_partnership_and_the_dalal_accepts_it(): void
    {
        $this->as($this->owner)->getJson('/api/v1/owner/dalals')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.0.id', $this->dalal->id)
            ->assertJsonPath('data.0.partnership', null);

        $id = $this->as($this->owner)->postJson("/api/v1/owner/dalals/{$this->dalal->id}/partnership", ['commission_pct' => 5, 'wage_pct' => 2, 'message' => 'تعامل'])
            ->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.status', DalalPartnership::PENDING)
            ->json('data.id');

        $this->as($this->owner)->postJson("/api/v1/owner/dalals/{$this->owner->id}/partnership", ['commission_pct' => 5])
            ->assertValidRequest()->assertValidResponse(404);

        $this->as($this->dalal)->getJson('/api/v1/dalal/partnerships?status=pending')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.owner.name', 'مالك الواجهة')
            ->assertJsonPath('data.0.commission_pct', 5);

        $this->as($this->dalal)->postJson("/api/v1/dalal/partnerships/{$id}/accept")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', DalalPartnership::ACCEPTED);

        $this->as($this->dalal)->postJson("/api/v1/dalal/partnerships/{$id}/reject", ['response_note' => 'متأخر'])
            ->assertValidRequest()->assertValidResponse(422);

        $this->as($this->owner)->getJson('/api/v1/owner/dalals')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.0.partnership.status', DalalPartnership::ACCEPTED);
    }

    public function test_the_dalal_sells_from_stock_collects_and_pays_the_owner(): void
    {
        DalalPartnership::factory()->accepted()->create(['owner_id' => $this->owner->id, 'dalal_id' => $this->dalal->id, 'commission_pct' => 5, 'wage_pct' => 2]);
        $trip = $this->consign(55);

        $this->as($this->dalal)->getJson('/api/v1/dalal/stock')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.summary.total_kg', 55)
            ->assertJsonPath('data.owners.0.owner', 'مالك الواجهة')
            ->assertJsonPath('data.owners.0.lots.0.trip_number', 'TR-2026-1501')
            ->assertJsonPath('data.species.0.name_sci', 'Epinephelus coioides');

        $customerId = $this->as($this->dalal)->postJson('/api/v1/dalal/customers', ['name' => 'علي علي', 'phone' => '0551112233'])
            ->assertValidRequest()->assertValidResponse(201)
            ->json('data.id');

        $this->as($this->dalal)->postJson('/api/v1/dalal/sales', [
            'customer_id' => $customerId,
            'items' => [['species_id' => $this->hamour->id, 'weight_kg' => 60, 'price_per_kg' => 36]],
        ])->assertValidRequest()->assertValidResponse(422)->assertJsonValidationErrors('items.0.weight_kg');

        $sale = $this->as($this->dalal)->postJson('/api/v1/dalal/sales', [
            'customer_id' => $customerId,
            'paid_amount' => 1000,
            'items' => [['species_id' => $this->hamour->id, 'trip_id' => $trip->id, 'weight_kg' => 42, 'price_per_kg' => 36]],
        ])->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.total', 1512)
            ->assertJsonPath('data.status', Sale::IN_PROGRESS)
            ->assertJsonPath('data.commission_amount', 75.6)
            ->assertJsonPath('data.wage_amount', 30.24)
            ->assertJsonPath('data.owner_net', 1406.16)
            ->assertJsonPath('data.items.0.owner.name', 'مالك الواجهة')
            ->json('data');

        $this->assertStringContainsString('/invoices/'.$sale['id'], $sale['invoice_url']);
        $this->get($sale['invoice_url'])->assertOk()->assertSee('1,512.00');

        $this->as($this->dalal)->postJson("/api/v1/dalal/sales/{$sale['id']}/payments", ['amount' => 512])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', Sale::COMPLETED)
            ->assertJsonPath('data.remaining', 0);

        $this->as($this->dalal)->getJson('/api/v1/dalal/sales?status='.urlencode(Sale::COMPLETED).'&min=1000')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.total_kg', 42);
        $this->as($this->dalal)->getJson("/api/v1/dalal/sales/{$sale['id']}")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.items.0.trip.trip_number', 'TR-2026-1501');

        $this->as($this->dalal)->getJson('/api/v1/dalal/owners')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.0.owner_net', 1406.16)
            ->assertJsonPath('data.0.in_stock_kg', 13)
            ->assertJsonPath('data.0.due', 1406.16);

        $this->as($this->dalal)->postJson("/api/v1/dalal/owners/{$this->owner->id}/payouts", ['amount' => 2000])
            ->assertValidRequest()->assertValidResponse(422);
        $this->as($this->dalal)->postJson("/api/v1/dalal/owners/{$this->owner->id}/payouts", ['amount' => 1406.16])
            ->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.due', 0);

        $this->as($this->dalal)->getJson('/api/v1/dalal/dashboard')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.kpis.sales_total', 1512)
            ->assertJsonPath('data.kpis.net_profit', 105.84)
            ->assertJsonPath('data.kpis.received_kg', 55)
            ->assertJsonPath('data.recent_sales.0.id', $sale['id']);
        $this->as($this->dalal)->getJson('/api/v1/dalal/dashboard?period=custom&from=2020-01-01&to=2020-01-31')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.kpis.sales_count', 0);

        foreach (['sales', 'stock', 'payouts', 'financial'] as $type) {
            $this->as($this->dalal)->getJson("/api/v1/dalal/reports/{$type}")
                ->assertValidRequest()->assertValidResponse(200)
                ->assertJsonPath('data.type', $type);
        }
        $this->as($this->dalal)->getJson('/api/v1/dalal/reports/sales')->assertJsonPath('data.totals.total', 1512);
    }

    public function test_customers_crud(): void
    {
        $id = $this->as($this->dalal)->postJson('/api/v1/dalal/customers', ['name' => 'مطعم'])
            ->assertValidRequest()->assertValidResponse(201)->json('data.id');

        $this->as($this->dalal)->getJson('/api/v1/dalal/customers?search=مطعم')
            ->assertValidRequest()->assertValidResponse(200)->assertJsonCount(1, 'data');
        $this->as($this->dalal)->putJson("/api/v1/dalal/customers/{$id}", ['name' => 'مطعم البحر'])
            ->assertValidRequest()->assertValidResponse(200)->assertJsonPath('data.name', 'مطعم البحر');
        $this->as($this->dalal)->getJson("/api/v1/dalal/customers/{$id}")
            ->assertValidRequest()->assertValidResponse(200);
        $this->as($this->dalal)->deleteJson("/api/v1/dalal/customers/{$id}")
            ->assertValidRequest()->assertValidResponse(200);
        $this->assertSame(0, Customer::forAccount($this->dalal)->count());
    }

    public function test_settings_profile_logo_and_workers(): void
    {
        Storage::fake('public');

        $this->as($this->dalal)->getJson('/api/v1/dalal/settings')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.dakka_name', null);

        $this->as($this->dalal)->putJson('/api/v1/dalal/settings', ['dakka_name' => 'دكة الواجهة', 'vat_number' => '300000000000001'])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.dakka_name', 'دكة الواجهة')
            ->assertJsonPath('data.vat_number', '300000000000001');

        $this->as($this->dalal)->post('/api/v1/dalal/settings/logo', ['logo' => UploadedFile::fake()->image('logo.png')], ['Accept' => 'application/json'])
            ->assertValidResponse(200);
        $this->assertNotNull($this->dalal->dalalProfile()->first()->logo_path);

        $type = DalalWorkerType::where('name', 'بائع')->sole();
        $this->as($this->dalal)->putJson('/api/v1/dalal/settings/workers', ['workers' => [['dalal_worker_type_id' => $type->id, 'nationality' => 'سعودي', 'count' => 3]]])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data.workers')
            ->assertJsonPath('data.workers.0.type.name', 'بائع');
        $this->as($this->dalal)->putJson('/api/v1/dalal/settings/workers', ['workers' => []])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(0, 'data.workers');

        $this->as($this->dalal)->deleteJson('/api/v1/dalal/settings/logo')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.logo_url', null);

        $this->as($this->dalal)->getJson('/api/v1/lookups')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.dalal_worker_types.0.name', 'بائع');
    }

    public function test_workers_are_individual_records_disabled_not_deleted_and_counts_follow_them(): void
    {
        Storage::fake('public');
        $seller = DalalWorkerType::where('name', 'بائع')->sole();
        $porter = DalalWorkerType::where('name', 'حمّال')->sole();

        // الأعداد القديمة تبقى ظاهرة حتى يُسجَّل أول عامل.
        $this->as($this->dalal)->putJson('/api/v1/dalal/settings/workers', ['workers' => [['dalal_worker_type_id' => $porter->id, 'nationality' => 'هندي', 'count' => 5]]])
            ->assertValidResponse(200)
            ->assertJsonPath('data.workers_source', 'counts')
            ->assertJsonPath('data.workers.0.count', 5);

        $created = $this->as($this->dalal)->postJson('/api/v1/dalal/workers', [
            'name' => 'محمد الحربي',
            'phone' => '+966 55 000 1111',
            'nationality' => 'سعودي',
            'id_number' => '1012345678',
            'dalal_worker_type_id' => $seller->id,
        ], ['Idempotency-Key' => 'worker-1'])->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.phone', '0550001111')
            ->assertJsonPath('data.status', 'نشط')
            ->assertJsonPath('data.type.name', 'بائع');
        $id = $created->json('data.id');

        $this->as($this->dalal)->postJson('/api/v1/dalal/workers', ['name' => 'سالم', 'nationality' => 'سعودي', 'dalal_worker_type_id' => $seller->id])->assertValidResponse(201);
        $this->as($this->dalal)->postJson('/api/v1/dalal/workers', ['name' => 'مكرر', 'id_number' => '1012345678', 'dalal_worker_type_id' => $seller->id])
            ->assertValidResponse(422)->assertJsonValidationErrors('id_number');

        $this->as($this->dalal)->getJson('/api/v1/dalal/workers?search=الحربي')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);

        // الأعداد محسوبة من العمال النشطين، والمسار القديم مرفوض الآن.
        $this->as($this->dalal)->getJson('/api/v1/dalal/settings')
            ->assertValidResponse(200)
            ->assertJsonPath('data.workers_source', 'records')
            ->assertJsonPath('data.workers.0.count', 2)
            ->assertJsonPath('data.workers.0.id', null);
        $this->as($this->dalal)->putJson('/api/v1/dalal/settings/workers', ['workers' => []])->assertValidResponse(422);

        // التعطيل بالحالة؛ لا حذف.
        $this->as($this->dalal)->putJson("/api/v1/dalal/workers/{$id}", ['name' => 'محمد الحربي', 'dalal_worker_type_id' => $seller->id, 'status' => 'غير نشط'])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', 'غير نشط');
        $this->as($this->dalal)->deleteJson("/api/v1/dalal/workers/{$id}")->assertStatus(405);
        $this->as($this->dalal)->getJson('/api/v1/dalal/workers?status=غير نشط')->assertJsonCount(1, 'data');
        $this->as($this->dalal)->getJson('/api/v1/dalal/settings')->assertJsonPath('data.workers.0.count', 1);

        // تعديل بلا حالة يُبقيها.
        $this->as($this->dalal)->putJson("/api/v1/dalal/workers/{$id}", ['name' => 'محمد', 'dalal_worker_type_id' => $seller->id])->assertJsonPath('data.status', 'غير نشط');

        $this->as($this->dalal)->post("/api/v1/dalal/workers/{$id}/avatar", ['avatar' => UploadedFile::fake()->image('w.jpg', 512, 512)], ['Accept' => 'application/json', 'Content-Type' => 'multipart/form-data'])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.avatar_url', fn ($url) => str_contains($url, '/storage/avatars/dalal-workers/'));
        $this->as($this->dalal)->deleteJson("/api/v1/dalal/workers/{$id}/avatar")->assertValidResponse(200)->assertJsonPath('data.avatar_url', null);

        // عامل دلال آخر 404.
        $other = User::factory()->dalal()->create();
        $this->as($other)->getJson("/api/v1/dalal/workers/{$id}")->assertValidResponse(404);
        $this->as($other)->putJson("/api/v1/dalal/workers/{$id}", ['name' => 'x', 'dalal_worker_type_id' => $seller->id])->assertValidResponse(404);
        $this->as($other)->getJson('/api/v1/dalal/workers')->assertJsonCount(0, 'data');
    }
}
