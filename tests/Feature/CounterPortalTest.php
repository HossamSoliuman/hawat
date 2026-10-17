<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Fisher;
use App\Models\Port;
use App\Models\Role;
use App\Models\Species;
use App\Models\StatisticsOfficer;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بوابة العدّاد في /admin/counter/*: شاشات التطبيق نفسها على الويب — رحلات
 * بحاجة لموافقتك (استلام)، عدّ المصيد بالصنف مع فحص الكمية وإضافة صنف،
 * التأكيد الذي يغذّي الإحصاء ويفتح البيع، والتقرير المفصّل — ورحلة ميناء
 * آخر لا تُرى.
 */
class CounterPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Fisher $captain;

    private User $counter;

    private Boat $boat;

    private Port $port;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        $this->owner = User::factory()->owner()->create(['name' => 'مالك الاختبار']);
        $this->port = Port::factory()->create(['name' => 'ميناء القطيف (اختبار)']);
        $this->captain = Fisher::factory()->captain()->ownedBy($this->owner)->create(['name' => 'كابتن الاختبار', 'port_id' => $this->port->id]);
        $this->boat = Boat::factory()->ownedBy($this->owner)->captainedBy($this->captain)->create(['port_id' => $this->port->id, 'name' => 'قارب الاختبار']);

        $this->counter = User::factory()->role(Role::COUNTER)->create(['name' => 'عدّاد الاختبار']);
        StatisticsOfficer::factory()->forUser($this->counter)->atPort($this->port)->create();
    }

    private function asCounter(): static
    {
        return $this->actingAs($this->counter);
    }

    /**
     * رحلة عادت بمصيدها إلى ميناء العدّاد — نقطة بدء كل اختبار هنا.
     */
    private function returnedTrip(array $attributes = [], array $catch = []): Trip
    {
        $trip = Trip::factory()->onBoat($this->boat)->captainedBy($this->captain)->create($attributes + [
            'return_port_id' => $this->port->id,
            'status' => Trip::AWAITING_COUNT,
            'started_at' => now()->subDays(2),
            'return_time' => now()->subHour(),
            'catch_submitted_at' => now()->subHour(),
            'captain_input_kg' => array_sum(array_column($catch, 'kg')),
        ]);

        foreach ($catch as $line) {
            CatchRecord::create([
                'trip_id' => $trip->id,
                'species_id' => $line['species']->id,
                'quantity_kg' => $line['kg'],
                'captain_kg' => $line['kg'],
                'captain_notes' => $line['notes'] ?? null,
                'recorded_at' => now()->toDateString(),
                'added_by' => $this->owner->id,
            ]);
        }

        return $trip;
    }

    public function test_counter_home_shows_the_port_queue_with_the_counter_sidebar(): void
    {
        $hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)']);
        $awaiting = $this->returnedTrip(['trip_number' => 'TR-2026-0701'], [['species' => $hamour, 'kg' => 120]]);
        $counting = $this->returnedTrip(['trip_number' => 'TR-2026-0702', 'status' => Trip::COUNTING, 'received_at' => now(), 'counter_id' => $this->counter->id]);

        $this->asCounter()->get('/admin')
            ->assertOk()
            ->assertSee('مرحبًا عدّاد الاختبار')
            ->assertSee('ميناء القطيف (اختبار)')
            ->assertSee('رحلات بحاجة لموافقتك')
            ->assertSee('TR-2026-0701')
            ->assertSee('TR-2026-0702')
            ->assertSee(route('panel.counter.trips.receive', $awaiting), false)
            ->assertSee(route('panel.counter.trips.show', $counting), false)
            ->assertSee(route('panel.notifications'), false)
            ->assertSee(route('panel.profile'), false)
            ->assertDontSee(route('panel.owner.trips'), false)
            ->assertDontSee(route('panel.users'), false);
    }

    public function test_counter_pages_are_for_counters_only_and_only_for_their_own_port(): void
    {
        $mine = $this->returnedTrip(['trip_number' => 'TR-2026-0710']);

        $otherPort = Port::factory()->create(['name' => 'ميناء جيزان (اختبار)']);
        $otherBoat = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $otherPort->id]);
        $foreign = Trip::factory()->onBoat($otherBoat)->create([
            'trip_number' => 'TR-2026-0711',
            'return_port_id' => $otherPort->id,
            'status' => Trip::AWAITING_COUNT,
        ]);

        // رحلة لم تعد بعد ليست من شأن العدّاد ولو كانت في ميناته.
        $atSea = Trip::factory()->onBoat($this->boat)->create(['trip_number' => 'TR-2026-0712', 'status' => Trip::AT_SEA]);

        $this->actingAs($this->owner)->get('/admin/counter/trips')->assertForbidden();
        $this->actingAs(User::factory()->role(Role::DALAL)->create())->get('/admin/counter/trips')->assertForbidden();
        $this->actingAs(User::factory()->superAdmin()->create())->get('/admin/counter/trips')->assertForbidden();

        $this->asCounter()->get('/admin/counter/trips')->assertOk()
            ->assertSee('TR-2026-0710')
            ->assertDontSee('TR-2026-0711')
            ->assertDontSee('TR-2026-0712');

        $this->asCounter()->get("/admin/counter/trips/{$foreign->id}")->assertNotFound();
        $this->asCounter()->post("/admin/counter/trips/{$foreign->id}/receive")->assertNotFound();
        $this->asCounter()->get("/admin/counter/trips/{$atSea->id}")->assertNotFound();
        $this->asCounter()->get("/admin/counter/trips/{$mine->id}")->assertOk();
        $this->asCounter()->get('/admin/owner/trips')->assertForbidden();
    }

    public function test_the_counter_receives_counts_and_confirms_so_the_catch_opens_for_sale(): void
    {
        $hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)']);
        $shaari = Species::factory()->create(['name_ar' => 'الشعري (اختبار)']);
        $safi = Species::factory()->create(['name_ar' => 'الصافي (اختبار)']);

        $trip = $this->returnedTrip(['trip_number' => 'TR-2026-0800'], [
            ['species' => $hamour, 'kg' => 100, 'notes' => 'صندوقان'],
            ['species' => $shaari, 'kg' => 40],
        ]);

        // قبل الاستلام: المخرجات تُعرض ولا حقول عد.
        $this->asCounter()->get("/admin/counter/trips/{$trip->id}")
            ->assertOk()
            ->assertSee('استلام وبدء عملية العد')
            ->assertSee('الهامور (اختبار)')
            ->assertSee('استلم الرحلة أولًا لتفتح حقول العد');

        // العد قبل الاستلام مباح (الرحلة في طور العد) — لكن الاستلام هو المسار.
        $this->asCounter()->post("/admin/counter/trips/{$trip->id}/receive")
            ->assertRedirect(route('panel.counter.trips.show', $trip));

        $trip->refresh();
        $this->assertSame(Trip::COUNTING, $trip->status);
        $this->assertSame($this->counter->id, $trip->counter_id);
        $this->assertSame('عدّاد الاختبار', $trip->statistics_officer);
        $this->assertNotNull($trip->received_at);

        // لا استلام مرتين.
        $this->asCounter()->from("/admin/counter/trips/{$trip->id}")->post("/admin/counter/trips/{$trip->id}/receive")
            ->assertSessionHasErrors('status');

        $this->asCounter()->get("/admin/counter/trips/{$trip->id}")
            ->assertOk()
            ->assertSee('عدّ المصيد')
            ->assertSee('تأكيد الكميات وإنهاء العد');

        // صنف واحد على الأقل.
        $this->asCounter()->from("/admin/counter/trips/{$trip->id}")->post("/admin/counter/trips/{$trip->id}/count", ['items' => []])
            ->assertSessionHasErrors('items');

        // العد: الهامور مطابق، الشعري أقلّ بملاحظة، والصافي صنف أضافه العدّاد.
        $this->asCounter()->post("/admin/counter/trips/{$trip->id}/count", [
            'items' => [
                ['species_id' => $hamour->id, 'weight_kg' => 100, 'verified' => 1],
                ['species_id' => $shaari->id, 'weight_kg' => 32.5, 'verified' => 0, 'notes' => 'فرق في الوزن'],
                ['species_id' => $safi->id, 'weight_kg' => 12, 'verified' => 1, 'notes' => 'أُضيف عند العد'],
            ],
            'notes' => 'عُدّ على الميزان الثاني',
        ])->assertRedirect(route('panel.counter.trips.show', $trip));

        $trip->refresh();
        $this->assertSame(Trip::AWAITING_APPROVAL, $trip->status);
        $this->assertEqualsWithDelta(144.5, (float) $trip->actual_weight_kg, 0.001);
        $this->assertEqualsWithDelta(4.5, (float) $trip->diff_kg, 0.001);
        $this->assertSame('عُدّ على الميزان الثاني', $trip->notes);
        $this->assertNotNull($trip->counted_at);
        $this->assertSame(3, $trip->catchRecords()->count());

        $added = $trip->catchRecords()->where('species_id', $safi->id)->first();
        $this->assertNull($added->captain_kg);
        $this->assertEqualsWithDelta(12, (float) $added->counted_kg, 0.001);
        $this->assertSame($this->counter->id, $added->added_by);

        $corrected = $trip->catchRecords()->where('species_id', $shaari->id)->first();
        $this->assertFalse((bool) $corrected->verified);
        $this->assertSame('فرق في الوزن', $corrected->counter_notes);
        $this->assertSame($this->counter->id, $corrected->corrected_by);

        // العد يفتح مصيد الرحلة للبيع عند مالكها ويُبلّغه.
        $this->assertSame(Trip::SALE_OPEN, $trip->sale_status);
        $this->assertSame(3, $trip->stockMovements()->count());
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->owner->id, 'trip_id' => $trip->id, 'title' => 'اكتمل العد']);
        $this->assertSame(1, (int) $this->counter->statisticsOfficer->fresh()->trips_counted);

        // الرحلة المعدودة تظهر في قائمة "معدودة" وفي صفحة الإحصاء المعتمد.
        $this->asCounter()->get('/admin/counter/trips?view=counted')->assertOk()->assertSee('TR-2026-0800');
        $this->asCounter()->get('/admin/counter/trips?view=awaiting')->assertOk()->assertDontSee('TR-2026-0800');
    }

    public function test_the_detailed_trip_report_shows_the_boat_licence_and_catch_lines(): void
    {
        $hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)']);
        $this->boat->update(['color' => 'أبيض', 'length_m' => 12.5, 'width_m' => 3.2, 'license_number' => 'BL-9001']);

        $trip = $this->returnedTrip([
            'trip_number' => 'TR-2026-0900',
            'license_number' => 'TL-5500',
            'crew_count' => 4,
        ], [['species' => $hamour, 'kg' => 80, 'notes' => 'صندوق']]);

        $this->asCounter()->get("/admin/counter/trips/{$trip->id}/report")
            ->assertOk()
            ->assertSee('تقرير مفصّل للرحلة')
            ->assertSee('TR-2026-0900')
            ->assertSee('TL-5500')
            ->assertSee('BL-9001')
            ->assertSee('أبيض')
            ->assertSee('مالك الاختبار')
            ->assertSee('كابتن الاختبار')
            ->assertSee('الهامور (اختبار)')
            ->assertSee('صندوق')
            ->assertSee('ميناء القطيف (اختبار)');
    }

    public function test_submitting_a_catch_notifies_the_port_counters(): void
    {
        $hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)']);
        $trip = Trip::factory()->onBoat($this->boat)->captainedBy($this->captain)->create([
            'trip_number' => 'TR-2026-1000',
            'return_port_id' => $this->port->id,
            'status' => Trip::AT_SEA,
            'started_at' => now()->subDay(),
        ]);

        $this->actingAs($this->owner)->post("/admin/owner/trips/{$trip->id}/catch", [
            'items' => [['species_id' => $hamour->id, 'weight_kg' => 90]],
        ])->assertRedirect();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $this->counter->id,
            'trip_id' => $trip->id,
            'title' => 'رحلة بانتظار العد',
        ]);

        // العدّاد يفتح الإشعار فيصل إلى صفحة العد في بوابته.
        $notification = AppNotification::forUser($this->counter)->first();
        $this->asCounter()->post("/admin/notifications/{$notification->id}/read")
            ->assertRedirect(route('panel.counter.trips.show', $trip));
    }

    public function test_a_counter_without_a_port_sees_an_empty_queue_and_is_told_why(): void
    {
        $this->returnedTrip(['trip_number' => 'TR-2026-1100']);
        $stray = User::factory()->role(Role::COUNTER)->create(['name' => 'عدّاد بلا ميناء']);

        $this->actingAs($stray)->get('/admin')
            ->assertOk()
            ->assertSee('حسابك غير مربوط بميناء')
            ->assertDontSee('TR-2026-1100');

        $this->actingAs($stray)->get('/admin/counter/trips')->assertOk()->assertDontSee('TR-2026-1100');
    }

    public function test_the_super_admin_creates_a_counter_with_a_port_and_the_profile_shows_it(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $role = Role::key(Role::COUNTER);

        // الميناء إلزامي للعدّاد.
        $this->actingAs($admin)->from(route('panel.users'))->post(route('panel.users.store'), [
            'name' => 'عدّاد جديد', 'phone' => '0500009999', 'role_id' => $role->id, 'password' => 'secret-pass-9',
        ])->assertSessionHasErrors('port_id');

        $this->actingAs($admin)->post(route('panel.users.store'), [
            'name' => 'عدّاد جديد', 'phone' => '0500009999', 'role_id' => $role->id,
            'password' => 'secret-pass-9', 'port_id' => $this->port->id,
        ])->assertRedirect(route('panel.users'));

        $created = User::where('phone', '0500009999')->firstOrFail();
        $this->assertSame($this->port->id, $created->statisticsOfficer->port_id);
        $this->assertSame('عدّاد جديد', $created->statisticsOfficer->name);

        $this->actingAs($created)->get('/admin/profile')
            ->assertOk()
            ->assertSee('عدّاد جديد')
            ->assertSee('ميناء القطيف (اختبار)');

        // نقله إلى ميناء آخر يُحدّث السجلّ نفسه لا يُنشئ ثانيًا.
        $other = Port::factory()->create(['name' => 'ميناء الدمام (اختبار)']);
        $this->actingAs($admin)->put(route('panel.users.update', $created), [
            'name' => 'عدّاد جديد', 'phone' => '0500009999', 'role_id' => $role->id, 'port_id' => $other->id,
        ])->assertRedirect(route('panel.users'));

        $this->assertSame(1, StatisticsOfficer::forUser($created)->count());
        $this->assertSame($other->id, $created->fresh()->statisticsOfficer->port_id);
    }
}
