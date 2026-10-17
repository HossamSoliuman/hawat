<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Boat;
use App\Models\CrewAdvance;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Fisher;
use App\Models\PaymentMethod;
use App\Models\Payroll;
use App\Models\PayType;
use App\Models\Port;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Species;
use App\Models\Trip;
use App\Models\User;
use App\Services\Owner\CrewPool;
use App\Services\Owner\ExpenseService;
use App\Services\Owner\PayrollService;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O3 — مال الطاقم: إعداد الأجور، نصيب الطاقم من صافي ربح القارب، توزيعه
 * بالنسبة الخاصة والأسهم، الرواتب الثابتة وترحيلها، السلف وخصمها، السداد
 * والتجميد، والطباعة وكشف الحساب.
 */
class OwnerCrewMoneyTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Boat $boat;

    private Trip $trip;

    private CarbonImmutable $month;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        $this->owner = User::factory()->owner()->create(['name' => 'مالك الطاقم']);
        $port = Port::factory()->create(['name' => 'ميناء الطاقم (اختبار)']);
        $this->boat = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $port->id, 'name' => 'قارب الطاقم', 'owner_share_percent' => 50]);
        $this->trip = Trip::factory()->forOwner($this->owner)->onBoat($this->boat)->create();
        $this->month = CarbonImmutable::now()->subMonth()->startOfMonth();
    }

    private function asOwner(): static
    {
        return $this->actingAs($this->owner);
    }

    private function member(string $type, array $attributes = [], ?Boat $boat = null): Fisher
    {
        return Fisher::factory()->ownedBy($this->owner)->onBoat($boat ?? $this->boat)->create($attributes + [
            'pay_type_id' => PayType::named($type)->id,
        ]);
    }

    private function captain(array $attributes = []): Fisher
    {
        return Fisher::factory()->captain()->ownedBy($this->owner)->onBoat($this->boat)->create($attributes + [
            'pay_type_id' => PayType::named(PayType::SHARE)->id,
        ]);
    }

    /**
     * بيع مباشر من المالك لمصيد رحلة القارب — صافيه إيراد القارب.
     */
    private function directSale(float $ownerNet, ?CarbonImmutable $at = null, ?Trip $trip = null): Sale
    {
        return Sale::factory()->create([
            'seller_id' => $this->owner->id,
            'trip_id' => ($trip ?? $this->trip)->id,
            'subtotal' => $ownerNet,
            'total' => $ownerNet,
            'owner_net' => $ownerNet,
            'sold_at' => ($at ?? $this->month->addDays(9))->setTime(10, 0),
        ]);
    }

    /**
     * بيع دلال لمصيد القارب — صافي المالك في سطر الفاتورة.
     */
    private function dalalSale(float $ownerNet): void
    {
        $sale = Sale::factory()->create([
            'seller_id' => User::factory()->dalal()->create()->id,
            'subtotal' => $ownerNet + 100,
            'total' => $ownerNet + 100,
            'sold_at' => $this->month->addDays(12)->setTime(9, 0),
        ]);

        SaleItem::create([
            'sale_id' => $sale->id,
            'species_id' => Species::factory()->create()->id,
            'trip_id' => $this->trip->id,
            'owner_id' => $this->owner->id,
            'weight_kg' => 100,
            'price_per_kg' => ($ownerNet + 100) / 100,
            'total' => $ownerNet + 100,
            'owner_net' => $ownerNet,
        ]);
    }

    private function boatExpense(float $amount, ?int $boatId = -1, ?CarbonImmutable $on = null): Expense
    {
        return Expense::factory()->ownedBy($this->owner)->create([
            'boat_id' => $boatId === -1 ? $this->boat->id : $boatId,
            'date' => ($on ?? $this->month->addDays(4))->toDateString(),
            'subtotal' => $amount,
            'total' => $amount,
        ]);
    }

    private function generate(?CarbonImmutable $month = null): Payroll
    {
        $month ??= $this->month;

        return app(PayrollService::class)->generate($this->owner, $this->boat, $month->year, $month->month)->load('lines');
    }

    public function test_pages_render_for_the_owner_only_and_sit_in_the_sidebar(): void
    {
        $crew = $this->member(PayType::FIXED, ['fixed_salary' => 1000]);
        $payroll = $this->generate();
        CrewAdvance::factory()->forFisher($crew)->create();

        foreach (['crew-pay', 'advances', "payrolls/{$payroll->id}", "payrolls/{$payroll->id}/print", "crew-pay/{$crew->id}/statement"] as $page) {
            $this->asOwner()->get("/admin/owner/{$page}")->assertOk()->assertSee($crew->name);
        }
        $this->asOwner()->get('/admin/owner/payrolls')->assertOk()->assertSee($payroll->payroll_number)->assertSee($this->boat->name);

        $this->asOwner()->get('/admin')
            ->assertSee(route('panel.owner.payrolls'), false)
            ->assertSee(route('panel.owner.advances'), false)
            ->assertSee(route('panel.owner.crew-pay'), false);

        $this->actingAs(User::factory()->dalal()->create())->get('/admin/owner/payrolls')->assertForbidden();
    }

    public function test_the_crew_pool_is_the_boats_net_profit_after_the_owners_share(): void
    {
        $this->directSale(10000);
        $this->dalalSale(2000);
        $this->directSale(5000, $this->month->subMonth()->addDays(3));   // شهر آخر
        $this->boatExpense(1500);
        $this->boatExpense(700, null);                                   // عام بلا قارب — لا يدخل
        Asset::factory()->ownedBy($this->owner)->create([
            'asset_type_id' => AssetType::named('محرك')->id,
            'boat_id' => $this->boat->id,
            'purchase_date' => $this->month->subYear()->toDateString(),
            'purchase_cost' => 12000,
            'salvage_value' => 0,
            'useful_life_years' => 5,
        ]);                                                              // 200 شهريًا

        $captain = $this->captain(['custom_share_percent' => 20]);
        $a = $this->member(PayType::SHARE, ['profit_shares' => 1]);
        $b = $this->member(PayType::SHARE, ['profit_shares' => 2]);
        $fixed = $this->member(PayType::FIXED, ['fixed_salary' => 1000]);

        $payroll = $this->generate();

        // الإيراد 12000 − المصروفات (1500 + راتب ثابت 1000) − الإهلاك 200 = 9300.
        $this->assertEqualsWithDelta(12000, $payroll->revenue, 0.001);
        $this->assertEqualsWithDelta(2500, $payroll->expenses, 0.001);
        $this->assertEqualsWithDelta(200, $payroll->depreciation_charged, 0.001);
        $this->assertEqualsWithDelta(9300, $payroll->net_profit, 0.001);
        $this->assertEqualsWithDelta(4650, $payroll->owner_share, 0.001);
        $this->assertEqualsWithDelta(4650, $payroll->crew_pool, 0.001);

        // الكابتن 20% من النصيب أولًا (930)، والباقي 3720 بالأسهم 1:2.
        $lines = $payroll->lines->keyBy('fisher_id');
        $this->assertEqualsWithDelta(930, $lines[$captain->id]->base_amount, 0.001);
        $this->assertTrue($lines[$captain->id]->is_captain);
        $this->assertEqualsWithDelta(1240, $lines[$a->id]->base_amount, 0.001);
        $this->assertEqualsWithDelta(2480, $lines[$b->id]->base_amount, 0.001);
        $this->assertEqualsWithDelta(1000, $lines[$fixed->id]->base_amount, 0.001);

        // الرواتب الثابتة سند واحد على القارب بتاريخ آخر الشهر.
        $expense = $payroll->expense()->first();
        $this->assertSame(ExpenseCategory::CREW_SALARIES, $expense->category->name);
        $this->assertEqualsWithDelta(1000, $expense->total, 0.001);
        $this->assertSame($this->month->endOfMonth()->toDateString(), $expense->date->toDateString());
        $this->assertSame($this->boat->id, $expense->boat_id);
        $this->assertTrue($expense->payment_follows_source);
    }

    public function test_depreciation_is_deferred_past_the_profit_and_a_loss_pays_the_crew_nothing(): void
    {
        $this->member(PayType::SHARE);
        $this->directSale(100);
        Asset::factory()->ownedBy($this->owner)->create([
            'asset_type_id' => AssetType::named('محرك')->id,
            'boat_id' => $this->boat->id,
            'purchase_date' => $this->month->subYear()->toDateString(),
            'purchase_cost' => 12000,
            'salvage_value' => 0,
            'useful_life_years' => 5,
        ]);

        $payroll = $this->generate();
        $this->assertEqualsWithDelta(100, $payroll->depreciation_charged, 0.001);
        $this->assertEqualsWithDelta(100, $payroll->depreciation_deferred, 0.001);
        $this->assertEqualsWithDelta(0, $payroll->net_profit, 0.001);
        $this->assertEqualsWithDelta(0, $payroll->crew_pool, 0.001);

        $pool = app(CrewPool::class)->forBoatMonth($this->owner, $this->boat->id, $this->month->subMonth()->year, $this->month->subMonth()->month, 50);
        $this->assertEqualsWithDelta(0, $pool['crew_pool'], 0.001);

        $this->boatExpense(500, -1, $this->month->subMonth()->addDays(2));
        $loss = app(CrewPool::class)->forBoatMonth($this->owner, $this->boat->id, $this->month->subMonth()->year, $this->month->subMonth()->month, 50);
        // الإهلاك لا يُحمَّل على خسارة (يؤجَّل كله)، والخسارة كلها على المالك.
        $this->assertEqualsWithDelta(-500, $loss['net_profit'], 0.001);
        $this->assertEqualsWithDelta(200, $loss['depreciation_deferred'], 0.001);
        $this->assertEqualsWithDelta(0, $loss['crew_pool'], 0.001);
        $this->assertEqualsWithDelta(-500, $loss['owner_share'], 0.001);
    }

    public function test_distribution_rounding_lands_on_the_last_share_holder(): void
    {
        $dues = app(CrewPool::class)->distribute(100, [
            ['key' => 1, 'shares' => 1, 'custom_percent' => null],
            ['key' => 2, 'shares' => 1, 'custom_percent' => null],
            ['key' => 3, 'shares' => 1, 'custom_percent' => null],
        ]);

        $this->assertSame([1 => 33.33, 2 => 33.33, 3 => 33.34], $dues);
        $this->assertEqualsWithDelta(100, array_sum($dues), 0.0001);
    }

    public function test_advances_are_deducted_up_to_the_pay_and_the_rest_carries_to_the_next_payroll(): void
    {
        $fixed = $this->member(PayType::FIXED, ['fixed_salary' => 1000]);
        $earlier = $this->month->subMonth();

        $this->asOwner()->post('/admin/owner/advances', [
            'fisher_id' => $fixed->id,
            'date' => $earlier->addDays(2)->toDateString(),
            'amount' => 1500,
            'payment_method_id' => PaymentMethod::query()->value('id'),
        ])->assertRedirect(route('panel.owner.advances'));

        // سلفة بعد نهاية الشهر لا تُخصم منه.
        CrewAdvance::factory()->forFisher($fixed)->create(['date' => $this->month->addDays(3)->toDateString(), 'amount' => 100]);

        $first = $this->generate($earlier);
        $line = $first->lines->first();
        $this->assertEqualsWithDelta(1000, $line->advances, 0.001);
        $this->assertEqualsWithDelta(0, $line->net, 0.001);

        $this->asOwner()->post("/admin/owner/payrolls/{$first->id}/lines/{$line->id}/pay")->assertRedirect();

        $second = $this->generate();
        $next = $second->lines->first();
        $this->assertEqualsWithDelta(600, $next->advances, 0.001);         // 500 متبقية + 100 الشهر
        $this->assertEqualsWithDelta(400, $next->net, 0.001);

        // السلفة المخصومة في مسير مسدَّد لا تُحذف؛ غير المخصومة تُحذف فيعود الصافي.
        $settled = CrewAdvance::where('amount', 1500)->first();
        $this->asOwner()->delete("/admin/owner/advances/{$settled->id}")->assertSessionHasErrors('advance');
        $this->assertModelExists($settled);

        $late = CrewAdvance::where('amount', 100)->first();
        $this->asOwner()->delete("/admin/owner/advances/{$late->id}")->assertRedirect();
        $this->assertEqualsWithDelta(500, $next->fresh()->net, 0.001);
    }

    public function test_paying_lines_freezes_them_and_the_salary_expense_follows(): void
    {
        $this->directSale(4000);
        $share = $this->member(PayType::SHARE);
        $fixed = $this->member(PayType::FIXED, ['fixed_salary' => 1000]);

        $payroll = $this->generate();
        $shareLine = $payroll->lines->firstWhere('fisher_id', $share->id);
        $fixedLine = $payroll->lines->firstWhere('fisher_id', $fixed->id);
        $this->assertEqualsWithDelta(1500, $shareLine->base_amount, 0.001);   // (4000 − 1000) × 50%

        $this->asOwner()->post("/admin/owner/payrolls/{$payroll->id}/lines/{$fixedLine->id}/pay")->assertRedirect();

        $expense = $payroll->expense()->first();
        $this->assertEqualsWithDelta(1000, $expense->paid_amount, 0.001);
        $this->assertSame(ExpenseService::PAID, $expense->paymentStatus->name);
        $this->assertSame(ExpenseService::PARTIAL, $payroll->fresh()->paymentStatus->name);

        // سداد السند من صفحة المصروفات مرفوض — يتبع المسير.
        $this->asOwner()->post("/admin/owner/expenses/{$expense->id}/payment", ['amount' => 1])->assertSessionHasErrors('amount');

        // بيع لاحق يرفع نصيب الطاقم للسطر غير المسدَّد عند فتح المسير.
        $this->directSale(2000);
        $this->asOwner()->get("/admin/owner/payrolls/{$payroll->id}")->assertOk();
        $this->assertEqualsWithDelta(2500, $shareLine->fresh()->base_amount, 0.001);

        $this->asOwner()->post("/admin/owner/payrolls/{$payroll->id}/pay-all")->assertRedirect();
        $this->assertSame(ExpenseService::PAID, $payroll->fresh()->paymentStatus->name);
        $this->assertEqualsWithDelta(2500, $shareLine->fresh()->paid_amount, 0.001);

        // المسير المسدَّد مجمَّد: بيع جديد لا يغيّره، ولا يُحذف.
        $this->directSale(9000);
        $this->asOwner()->get("/admin/owner/payrolls/{$payroll->id}")->assertOk();
        $this->assertEqualsWithDelta(2500, $shareLine->fresh()->base_amount, 0.001);
        $this->asOwner()->delete("/admin/owner/payrolls/{$payroll->id}")->assertSessionHasErrors('payroll');
        $this->assertModelExists($payroll);
    }

    public function test_bonus_and_deduction_adjust_the_line_and_the_fixed_expense(): void
    {
        $fixed = $this->member(PayType::FIXED, ['fixed_salary' => 1000]);
        $payroll = $this->generate();
        $line = $payroll->lines->first();

        $this->asOwner()->put("/admin/owner/payrolls/{$payroll->id}/lines/{$line->id}", ['deduction' => 2000])->assertSessionHasErrors('deduction');

        $this->asOwner()->put("/admin/owner/payrolls/{$payroll->id}/lines/{$line->id}", ['bonus' => 300, 'deduction' => 100, 'notes' => 'مكافأة موسم'])->assertRedirect();

        $line->refresh();
        $this->assertEqualsWithDelta(1200, $line->net, 0.001);
        $this->assertSame('مكافأة موسم', $line->notes);
        $this->assertEqualsWithDelta(1200, $payroll->expense()->first()->total, 0.001);
    }

    public function test_membership_follows_the_boat_until_a_line_is_paid(): void
    {
        $stays = $this->member(PayType::SHARE);
        $leaves = $this->member(PayType::SHARE);
        $payroll = $this->generate();
        $this->assertCount(2, $payroll->lines);

        $other = Boat::factory()->ownedBy($this->owner)->create();
        $leaves->update(['boat_id' => $other->id]);
        $joins = $this->member(PayType::FIXED, ['fixed_salary' => 800]);
        $this->member(PayType::SHARE, ['status' => 'غير نشط']);
        Fisher::factory()->ownedBy($this->owner)->onBoat($this->boat)->create();   // بلا إعداد أجر

        $this->asOwner()->get("/admin/owner/payrolls/{$payroll->id}")->assertOk();

        $this->assertEqualsCanonicalizing([$stays->id, $joins->id], $payroll->lines()->pluck('fisher_id')->all());
    }

    public function test_generation_rules(): void
    {
        $this->asOwner()->post('/admin/owner/payrolls', ['boat_id' => $this->boat->id, 'period' => $this->month->format('Y-m')])
            ->assertSessionHasErrors('boat_id');                                  // لا أفراد بإعداد أجر

        $this->member(PayType::SHARE);

        $this->asOwner()->post('/admin/owner/payrolls', ['boat_id' => $this->boat->id, 'period' => now()->addMonth()->format('Y-m')])
            ->assertSessionHasErrors('period');

        $response = $this->asOwner()->post('/admin/owner/payrolls', ['boat_id' => $this->boat->id, 'period' => $this->month->format('Y-m')]);
        $payroll = Payroll::sole();
        $response->assertRedirect(route('panel.owner.payrolls.show', $payroll->id));

        // الشهر نفسه مرة أخرى يفتح الموجود.
        $this->asOwner()->post('/admin/owner/payrolls', ['boat_id' => $this->boat->id, 'period' => $this->month->format('Y-m')])
            ->assertRedirect(route('panel.owner.payrolls.show', $payroll->id));
        $this->assertSame(1, Payroll::count());

        // مسير بلا سداد يُحذف ومعه سنده غير المدفوع.
        $this->asOwner()->delete("/admin/owner/payrolls/{$payroll->id}")->assertRedirect(route('panel.owner.payrolls'));
        $this->assertModelMissing($payroll);
    }

    public function test_pay_settings_and_the_owner_share(): void
    {
        $captain = $this->captain(['custom_share_percent' => 70]);
        $crew = $this->member(PayType::SHARE);
        $share = PayType::named(PayType::SHARE)->id;
        $fixed = PayType::named(PayType::FIXED)->id;

        $this->asOwner()->put("/admin/owner/crew-pay/{$crew->id}", ['pay_type_id' => $share, 'custom_share_percent' => 40])
            ->assertSessionHasErrors('custom_share_percent');                     // 70 + 40 > 100

        $this->asOwner()->put("/admin/owner/crew-pay/{$crew->id}", ['pay_type_id' => $fixed])
            ->assertSessionHasErrors('fixed_salary');

        $this->asOwner()->put("/admin/owner/crew-pay/{$crew->id}", ['pay_type_id' => $fixed, 'fixed_salary' => 2500, 'custom_share_percent' => 10])
            ->assertRedirect(route('panel.owner.crew-pay'));
        $crew->refresh();
        $this->assertEqualsWithDelta(2500, $crew->fixed_salary, 0.001);
        $this->assertNull($crew->custom_share_percent);                         // النسبة الخاصة لأصحاب الحصص فقط

        $this->asOwner()->put("/admin/owner/crew-pay/{$captain->id}", ['pay_type_id' => $share, 'profit_shares' => 1.5])->assertRedirect();
        $this->assertEqualsWithDelta(1.5, $captain->fresh()->profit_shares, 0.001);
        $this->assertNull($captain->fresh()->custom_share_percent);

        $this->asOwner()->put("/admin/owner/crew-pay/boats/{$this->boat->id}", ['owner_share_percent' => 60])->assertRedirect();
        $this->assertEqualsWithDelta(60, $this->boat->fresh()->owner_share_percent, 0.001);
        $this->asOwner()->put("/admin/owner/crew-pay/boats/{$this->boat->id}", ['owner_share_percent' => 120])->assertSessionHasErrors('owner_share_percent');
    }

    public function test_another_owners_records_are_refused(): void
    {
        $crew = $this->member(PayType::SHARE);
        $payroll = $this->generate();
        $advance = CrewAdvance::factory()->forFisher($crew)->create();
        $line = $payroll->lines->first();

        $stranger = User::factory()->owner()->create();

        $this->actingAs($stranger)->get("/admin/owner/payrolls/{$payroll->id}")->assertNotFound();
        $this->actingAs($stranger)->get("/admin/owner/payrolls/{$payroll->id}/print")->assertNotFound();
        $this->actingAs($stranger)->post("/admin/owner/payrolls/{$payroll->id}/lines/{$line->id}/pay")->assertNotFound();
        $this->actingAs($stranger)->delete("/admin/owner/advances/{$advance->id}")->assertNotFound();
        $this->actingAs($stranger)->get("/admin/owner/crew-pay/{$crew->id}/statement")->assertNotFound();
        $this->actingAs($stranger)->put("/admin/owner/crew-pay/{$crew->id}", ['pay_type_id' => $crew->pay_type_id])->assertNotFound();
        $this->actingAs($stranger)->post('/admin/owner/advances', ['fisher_id' => $crew->id, 'date' => now()->toDateString(), 'amount' => 50])->assertSessionHasErrors('fisher_id');
        $this->actingAs($stranger)->post('/admin/owner/payrolls', ['boat_id' => $this->boat->id, 'period' => $this->month->format('Y-m')])->assertSessionHasErrors('boat_id');

        // سطر مسير آخر عبر مسير المالك نفسه = 404 أيضًا.
        $otherBoat = Boat::factory()->ownedBy($stranger)->create();
        Fisher::factory()->ownedBy($stranger)->onBoat($otherBoat)->create(['pay_type_id' => PayType::named(PayType::SHARE)->id]);
        $foreign = app(PayrollService::class)->generate($stranger, $otherBoat, $this->month->year, $this->month->month);
        $this->asOwner()->post("/admin/owner/payrolls/{$payroll->id}/lines/{$foreign->lines()->value('id')}/pay")->assertNotFound();
    }

    public function test_the_statement_lists_payrolls_and_advances(): void
    {
        $fixed = $this->member(PayType::FIXED, ['fixed_salary' => 1000]);
        CrewAdvance::factory()->forFisher($fixed)->create(['date' => $this->month->addDay()->toDateString(), 'amount' => 300]);
        $payroll = $this->generate();

        $this->asOwner()->get("/admin/owner/crew-pay/{$fixed->id}/statement")
            ->assertOk()
            ->assertSee($payroll->payroll_number)
            ->assertSee('700.00')
            ->assertSee('300.00');
    }
}
