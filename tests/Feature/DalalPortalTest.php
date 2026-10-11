<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Customer;
use App\Models\DalalPartnership;
use App\Models\DalalPayout;
use App\Models\DalalProfile;
use App\Models\DalalWorker;
use App\Models\DalalWorkerType;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Species;
use App\Models\Trip;
use App\Models\User;
use App\Services\Notifications\Notifier;
use App\Services\Sales\SaleService;
use App\Services\Stock\StockLedger;
use App\Services\Trips\TripService;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * بوابة الدلال في /admin/dalal/*: المخزون مما أرسله الملاك مجمّعًا حسب
 * المالك، والبيع منه بالسطور (الأقدم أوّلًا) مع العمولة والأجور من اتفاق
 * المالك المقبول، والفاتورة المطبوعة، والعملاء، وطلبات الملاك، والصيّادون
 * المرتبطون ودفعاتهم، والتقارير، والإعدادات — وسجلات دلال آخر لا تُرى.
 */
class DalalPortalTest extends TestCase
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

        $this->owner = User::factory()->owner()->create(['name' => 'مالك الاختبار']);
        $this->dalal = User::factory()->dalal()->create(['name' => 'دلال الاختبار']);
        $this->hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)', 'name_sci' => 'Epinephelus coioides']);
    }

    private function asDalal(): static
    {
        return $this->actingAs($this->dalal);
    }

    /**
     * رحلة معدودة مفتوحة للبيع لمالك، ثم يرسل منها إلى الدلال.
     */
    private function consign(User $owner, float $kg, ?Species $species = null, string $tripNumber = 'TR-2026-0801', float $counted = 200): Trip
    {
        $species ??= $this->hamour;
        $boat = Boat::factory()->ownedBy($owner)->create();
        $trip = Trip::factory()->onBoat($boat)->forOwner($owner)->create([
            'trip_number' => $tripNumber,
            'status' => Trip::AWAITING_APPROVAL,
            'counted_at' => now(),
        ]);
        CatchRecord::create(['trip_id' => $trip->id, 'species_id' => $species->id, 'quantity_kg' => $counted, 'counted_kg' => $counted, 'recorded_at' => now()->toDateString()]);

        app(TripService::class)->openForSale($trip->fresh());
        app(SaleService::class)->consign($owner, ['trip_id' => $trip->id, 'dalal_id' => $this->dalal->id, 'items' => [['species_id' => $species->id, 'weight_kg' => $kg]]]);

        return $trip;
    }

    private function accept(User $owner, float $commission = 5, float $wage = 2): DalalPartnership
    {
        return DalalPartnership::factory()->accepted()->create([
            'owner_id' => $owner->id,
            'dalal_id' => $this->dalal->id,
            'commission_pct' => $commission,
            'wage_pct' => $wage,
        ]);
    }

    public function test_dalal_home_shows_the_dashboard_with_the_dalal_sidebar(): void
    {
        $this->consign($this->owner, 60);
        DalalPartnership::factory()->create(['owner_id' => User::factory()->owner()->create(['name' => 'مالك يطلب'])->id, 'dalal_id' => $this->dalal->id]);

        $this->asDalal()->get('/admin')
            ->assertOk()
            ->assertSee('مرحبًا دلال الاختبار')
            ->assertSee('المصيد المستلم')
            ->assertSee('60.0')
            ->assertSee('مالك يطلب')
            ->assertSee(route('panel.dalal.stock'), false)
            ->assertSee(route('panel.dalal.sales'), false)
            ->assertSee(route('panel.dalal.requests'), false)
            ->assertSee(route('panel.dalal.settings'), false)
            ->assertDontSee(route('panel.owner.trips'), false)
            ->assertDontSee(route('panel.counter.trips'), false)
            ->assertDontSee(route('panel.users'), false);

        $this->asDalal()->get('/admin?period=today')->assertOk()->assertSee('اليوم');
        $this->asDalal()->get('/admin?period=custom&from=2026-01-01&to=2026-01-31')->assertOk()->assertSee('2026-01-01');
    }

    public function test_dalal_pages_are_for_dalals_only(): void
    {
        foreach (['stock', 'sales', 'sales/create', 'customers', 'requests', 'owners', 'reports', 'settings'] as $page) {
            $this->actingAs($this->owner)->get("/admin/dalal/{$page}")->assertForbidden();
            $this->asDalal()->get("/admin/dalal/{$page}")->assertOk();
        }

        $this->asDalal()->get('/admin/owner/trips')->assertForbidden();
    }

    public function test_consigning_notifies_the_dalal_and_stock_is_grouped_by_owner(): void
    {
        $this->consign($this->owner, 55, null, 'TR-2026-0802');

        $notification = AppNotification::where('user_id', $this->dalal->id)->sole();
        $this->assertSame(Notifier::STOCK_RECEIVED, $notification->type->name);
        $this->assertSame('stock', $notification->data['target']);

        $this->asDalal()->get('/admin/dalal/stock')
            ->assertOk()
            ->assertSee('مالك الاختبار')
            ->assertSee('55.00 كجم')
            ->assertSee('الهامور (اختبار)')
            ->assertSee('Epinephelus coioides')
            ->assertSee('TR-2026-0802');

        // فتح الإشعار يذهب إلى المخزون لا إلى صفحة رحلة.
        $this->asDalal()->post(route('panel.notifications.read', $notification))->assertRedirect(route('panel.dalal.stock'));
    }

    public function test_sale_takes_the_oldest_stock_first_and_applies_each_owner_terms(): void
    {
        $second = User::factory()->owner()->create(['name' => 'مالك ثانٍ']);
        $this->accept($this->owner, 5, 2);
        $this->accept($second, 10, 0);

        $first = $this->consign($this->owner, 30, null, 'TR-2026-0803');
        $later = $this->consign($second, 50, null, 'TR-2026-0804');
        $customer = Customer::factory()->create(['account_user_id' => $this->dalal->id, 'name' => 'مطعم الاختبار']);

        $this->asDalal()->post('/admin/dalal/sales', [
            'customer_id' => $customer->id,
            'items' => [['species_id' => $this->hamour->id, 'weight_kg' => 40, 'price_per_kg' => 50]],
        ])->assertRedirect();

        $sale = Sale::forSeller($this->dalal)->with('items')->sole();
        $this->assertSame(2000.0, (float) $sale->total);
        $this->assertSame(Sale::COMPLETED, $sale->status);

        // 30 كجم من رحلة المالك الأول (الأقدم) ثم 10 من الثاني.
        $this->assertEqualsCanonicalizing([[$first->id, 30.0], [$later->id, 10.0]], $sale->items->map(fn ($i) => [$i->trip_id, (float) $i->weight_kg])->all());

        $mine = $sale->items->firstWhere('owner_id', $this->owner->id);
        $this->assertSame(75.0, (float) $mine->commission_amount);   // 1500 × 5%
        $this->assertSame(30.0, (float) $mine->wage_amount);         // 1500 × 2%
        $this->assertSame(1395.0, (float) $mine->owner_net);

        $theirs = $sale->items->firstWhere('owner_id', $second->id);
        $this->assertSame(50.0, (float) $theirs->commission_amount); // 500 × 10%
        $this->assertSame(450.0, (float) $theirs->owner_net);
        $this->assertSame(1845.0, (float) $sale->owner_net);

        $ledger = app(StockLedger::class);
        $this->assertSame(0.0, $ledger->availableFor($this->dalal, $first, $this->hamour->id));
        $this->assertSame(40.0, $ledger->availableFor($this->dalal, $later, $this->hamour->id));

        $this->assertSame(1, AppNotification::where('user_id', $this->owner->id)->whereHas('type', fn ($q) => $q->where('name', Notifier::DALAL_SOLD))->count());
        $this->assertSame(1, AppNotification::where('user_id', $second->id)->whereHas('type', fn ($q) => $q->where('name', Notifier::DALAL_SOLD))->count());

        $this->asDalal()->get(route('panel.dalal.sales.show', $sale))
            ->assertOk()
            ->assertSee($sale->invoice_number)
            ->assertSee('مطعم الاختبار')
            ->assertSee('1,845.00')
            ->assertSee($sale->invoiceUrl(), false);
    }

    public function test_a_sale_may_name_its_lot_and_never_exceeds_the_stock(): void
    {
        $first = $this->consign($this->owner, 30, null, 'TR-2026-0805');
        $later = $this->consign($this->owner, 20, null, 'TR-2026-0806');

        $this->asDalal()->post('/admin/dalal/sales', [
            'items' => [['species_id' => $this->hamour->id, 'trip_id' => $later->id, 'weight_kg' => 25, 'price_per_kg' => 10]],
        ])->assertSessionHasErrors('items.0.weight_kg');

        $this->asDalal()->post('/admin/dalal/sales', [
            'items' => [
                ['species_id' => $this->hamour->id, 'weight_kg' => 40, 'price_per_kg' => 10],
                ['species_id' => $this->hamour->id, 'weight_kg' => 15, 'price_per_kg' => 10],
            ],
        ])->assertSessionHasErrors('items.1.weight_kg');

        $this->assertSame(0, Sale::forSeller($this->dalal)->count());

        $this->asDalal()->post('/admin/dalal/sales', [
            'items' => [['species_id' => $this->hamour->id, 'trip_id' => $later->id, 'weight_kg' => 20, 'price_per_kg' => 10]],
        ])->assertRedirect();

        $this->assertSame([$later->id], Sale::forSeller($this->dalal)->sole()->items->pluck('trip_id')->all());
        $this->assertSame(30.0, app(StockLedger::class)->availableFor($this->dalal, $first, $this->hamour->id));
    }

    public function test_a_partly_paid_sale_stays_in_progress_until_collected(): void
    {
        $this->consign($this->owner, 30);

        $this->asDalal()->post('/admin/dalal/sales', [
            'paid_amount' => 100,
            'items' => [['species_id' => $this->hamour->id, 'weight_kg' => 10, 'price_per_kg' => 30]],
        ])->assertRedirect();

        $sale = Sale::forSeller($this->dalal)->sole();
        $this->assertSame(Sale::IN_PROGRESS, $sale->status);
        $this->assertSame(200.0, $sale->remaining);

        $this->asDalal()->post(route('panel.dalal.sales.payment', $sale), ['amount' => 500])->assertSessionHasErrors('amount');
        $this->asDalal()->post(route('panel.dalal.sales.payment', $sale), ['amount' => 200])->assertRedirect(route('panel.dalal.sales.show', $sale));

        $this->assertSame(Sale::COMPLETED, $sale->fresh()->status);
        $this->assertSame(0.0, $sale->fresh()->remaining);
    }

    public function test_the_printed_invoice_needs_a_signed_link(): void
    {
        $this->consign($this->owner, 30);
        DalalProfile::forUser($this->dalal)->update(['company_name' => 'مؤسسة الدلال للاختبار', 'vat_number' => '300000000000099']);
        $sale = app(SaleService::class)->sellFromStock($this->dalal, ['items' => [['species_id' => $this->hamour->id, 'weight_kg' => 12, 'price_per_kg' => 36]]]);

        $this->get($sale->invoiceUrl())
            ->assertOk()
            ->assertSee('فاتورة صيد بحري')
            ->assertSee('مؤسسة الدلال للاختبار')
            ->assertSee('300000000000099')
            ->assertSee($sale->invoice_number)
            ->assertSee('432.00');

        $this->get("/invoices/{$sale->id}")->assertForbidden();
    }

    public function test_other_dalals_records_are_not_found(): void
    {
        $other = User::factory()->dalal()->create();
        $theirCustomer = Customer::factory()->create(['account_user_id' => $other->id]);
        $theirRequest = DalalPartnership::factory()->create(['dalal_id' => $other->id]);
        $theirSale = Sale::factory()->create(['seller_id' => $other->id]);
        $theirWorker = DalalWorker::factory()->create(['dalal_id' => $other->id]);

        $this->asDalal()->get(route('panel.dalal.sales.show', $theirSale))->assertNotFound();
        $this->asDalal()->post(route('panel.dalal.sales.payment', $theirSale), ['amount' => 1])->assertNotFound();
        $this->asDalal()->put(route('panel.dalal.customers.update', $theirCustomer), ['name' => 'x'])->assertNotFound();
        $this->asDalal()->delete(route('panel.dalal.customers.destroy', $theirCustomer))->assertNotFound();
        $this->asDalal()->post(route('panel.dalal.requests.accept', $theirRequest))->assertNotFound();
        $this->asDalal()->delete(route('panel.dalal.settings.workers.destroy', $theirWorker))->assertNotFound();
        $this->asDalal()->post(route('panel.dalal.owners.payout', $this->owner), ['amount' => 1])->assertNotFound();

        // عميل دلال آخر لا يُختار في البيع.
        $this->consign($this->owner, 10);
        $this->asDalal()->post('/admin/dalal/sales', [
            'customer_id' => $theirCustomer->id,
            'items' => [['species_id' => $this->hamour->id, 'weight_kg' => 1, 'price_per_kg' => 1]],
        ])->assertSessionHasErrors('customer_id');
    }

    public function test_customers_are_managed_per_dalal(): void
    {
        $this->asDalal()->post('/admin/dalal/customers', ['name' => 'أسماك الواحة', 'phone' => '+966 55 123 4567'])->assertRedirect(route('panel.dalal.customers'));

        $customer = Customer::forAccount($this->dalal)->sole();
        $this->assertSame('0551234567', $customer->phone);

        $this->asDalal()->get('/admin/dalal/customers')->assertOk()->assertSee('أسماك الواحة');
        $this->actingAs($this->owner)->get('/admin/owner/customers')->assertOk()->assertDontSee('أسماك الواحة');

        $this->asDalal()->put(route('panel.dalal.customers.update', $customer), ['name' => 'أسماك الواحة الجديدة'])->assertRedirect();
        $this->assertSame('أسماك الواحة الجديدة', $customer->fresh()->name);

        $this->asDalal()->delete(route('panel.dalal.customers.destroy', $customer))->assertRedirect();
        $this->assertModelMissing($customer);
    }

    public function test_owner_requests_a_partnership_and_the_dalal_answers_it(): void
    {
        $this->actingAs($this->owner)->get('/admin/owner/dalals')->assertOk()->assertSee('دلال الاختبار')->assertSee('اقتراح عمولة');

        $this->actingAs($this->owner)->post(route('panel.owner.dalals.partnership', $this->dalal), [
            'commission_pct' => 6,
            'wage_pct' => 1.5,
            'message' => 'نرغب بالتعامل معك',
        ])->assertRedirect(route('panel.owner.dalals'));

        $partnership = DalalPartnership::sole();
        $this->assertTrue($partnership->isPending());
        $this->assertSame(1, AppNotification::where('user_id', $this->dalal->id)->count());

        $this->asDalal()->get('/admin/dalal/requests?status=pending')->assertOk()->assertSee('مالك الاختبار')->assertSee('نرغب بالتعامل معك')->assertSee('6%');

        $this->asDalal()->post(route('panel.dalal.requests.accept', $partnership))->assertRedirect(route('panel.dalal.requests'));
        $this->assertSame(DalalPartnership::ACCEPTED, $partnership->fresh()->status);
        $this->assertSame(Notifier::PARTNERSHIP_ACCEPTED, AppNotification::where('user_id', $this->owner->id)->sole()->type->name);

        // المقبول لا يُعاد طلبه، ولا يُردّ عليه مرة ثانية.
        $this->actingAs($this->owner)->post(route('panel.owner.dalals.partnership', $this->dalal), ['commission_pct' => 3])->assertSessionHasErrors('partnership');
        $this->asDalal()->post(route('panel.dalal.requests.reject', $partnership))->assertSessionHasErrors('partnership');

        // الطلب لا يُرسل إلى غير دلال.
        $this->actingAs($this->owner)->post(route('panel.owner.dalals.partnership', User::factory()->owner()->create()), ['commission_pct' => 3])->assertNotFound();
    }

    public function test_a_rejected_request_can_be_sent_again(): void
    {
        $partnership = DalalPartnership::factory()->create(['owner_id' => $this->owner->id, 'dalal_id' => $this->dalal->id, 'commission_pct' => 2]);

        $this->asDalal()->post(route('panel.dalal.requests.reject', $partnership), ['response_note' => 'العمولة قليلة'])->assertRedirect();
        $this->assertSame(DalalPartnership::REJECTED, $partnership->fresh()->status);
        $this->actingAs($this->owner)->get('/admin/owner/dalals')->assertSee('العمولة قليلة');

        $this->actingAs($this->owner)->post(route('panel.owner.dalals.partnership', $this->dalal), ['commission_pct' => 4])->assertRedirect();
        $this->assertTrue($partnership->fresh()->isPending());
        $this->assertSame(4.0, (float) $partnership->fresh()->commission_pct);
    }

    public function test_linked_owners_show_their_account_and_receive_payouts(): void
    {
        $this->accept($this->owner, 10, 0);
        $this->consign($this->owner, 50);
        app(SaleService::class)->sellFromStock($this->dalal, ['items' => [['species_id' => $this->hamour->id, 'weight_kg' => 20, 'price_per_kg' => 50]]]);

        $this->asDalal()->get('/admin/dalal/owners')
            ->assertOk()
            ->assertSee('مالك الاختبار')
            ->assertSee('900.00');   // 1000 − 10%

        $this->asDalal()->post(route('panel.dalal.owners.payout', $this->owner), ['amount' => 901])->assertSessionHasErrors('amount');
        $this->asDalal()->post(route('panel.dalal.owners.payout', $this->owner), ['amount' => 400])->assertRedirect(route('panel.dalal.owners'));

        $this->assertSame(400.0, (float) DalalPayout::sole()->amount);
        $this->assertSame(Notifier::PAYOUT_RECEIVED, AppNotification::where('user_id', $this->owner->id)->latest('id')->first()->type->name);

        $this->actingAs($this->owner)->get('/admin/owner/dalals')->assertOk()->assertSee('900.00')->assertSee('500.00');
    }

    public function test_reports_render_each_type_with_totals(): void
    {
        $this->accept($this->owner, 5, 0);
        $this->consign($this->owner, 40);
        app(SaleService::class)->sellFromStock($this->dalal, ['items' => [['species_id' => $this->hamour->id, 'weight_kg' => 10, 'price_per_kg' => 20]]]);

        $this->asDalal()->get('/admin/dalal/reports')->assertOk()->assertSee('تقرير المبيعات')->assertSee('التقرير المالي');

        $this->asDalal()->get('/admin/dalal/reports/sales')->assertOk()->assertSee('200.00')->assertSee('10.00')->assertSee('المجموع');
        $this->asDalal()->get('/admin/dalal/reports/stock')->assertOk()->assertSee('الهامور (اختبار)')->assertSee('40.00')->assertSee('30.00');
        $this->asDalal()->get('/admin/dalal/reports/payouts')->assertOk()->assertSee('مالك الاختبار')->assertSee('190.00');
        $this->asDalal()->get('/admin/dalal/reports/financial')->assertOk()->assertSee('صافي الربح');
        $this->asDalal()->get('/admin/dalal/reports/unknown')->assertNotFound();
    }

    public function test_settings_save_the_trade_profile_logo_and_workers(): void
    {
        Storage::fake('public');

        $this->asDalal()->put('/admin/dalal/settings', [
            'dakka_name' => 'دكة الاختبار',
            'dakka_number' => 'D-7',
            'cr_number' => '1010101010',
        ])->assertRedirect();

        $this->asDalal()->put('/admin/dalal/settings', ['company_name' => 'مؤسسة الاختبار', 'website' => 'not a url'])->assertSessionHasErrors('website');
        $this->asDalal()->put('/admin/dalal/settings', ['company_name' => 'مؤسسة الاختبار'])->assertRedirect();

        $profile = DalalProfile::forUser($this->dalal);
        $this->assertSame('دكة الاختبار', $profile->dakka_name);
        $this->assertSame('1010101010', $profile->cr_number);
        $this->assertSame('مؤسسة الاختبار', $profile->company_name);

        $this->asDalal()->post('/admin/dalal/settings/logo', ['logo' => UploadedFile::fake()->image('logo.png', 200, 200)])->assertRedirect();
        Storage::disk('public')->assertExists($profile->fresh()->logo_path);

        $type = DalalWorkerType::where('name', 'حمّال')->sole();
        $this->asDalal()->post('/admin/dalal/settings/workers', ['dalal_worker_type_id' => $type->id, 'nationality' => 'هندي', 'count' => 4])->assertRedirect();
        $this->asDalal()->get('/admin/dalal/settings?tab=workers')->assertOk()->assertSee('حمّال')->assertSee('هندي');

        $this->asDalal()->delete(route('panel.dalal.settings.workers.destroy', DalalWorker::sole()))->assertRedirect();
        $this->assertSame(0, DalalWorker::count());

        $this->asDalal()->get('/admin/profile')->assertOk();
        $this->assertSame(Role::DALAL, $this->dalal->fresh()->app_role_key);
    }
}
