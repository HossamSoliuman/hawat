<?php

namespace Tests\Feature\Api;

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
use Laravel\Sanctum\Sanctum;
use Spectator\Spectator;
use Tests\TestCase;

/**
 * واجهة العدّاد /api/v1/counter/* — كل ردّ يُطابَق مع docs/api/openapi.yaml
 * (Spectator).
 *
 * العدّاد يرى ما عاد إلى ميناء عمله وحده، ويستلمه ويعدّه بحسابه هو، ويقرأ
 * تقرير الرحلة المفصّل.
 */
class CounterApiTest extends TestCase
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

        Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');

        $this->owner = User::factory()->owner()->create(['name' => 'مالك الواجهة']);
        $this->port = Port::factory()->create(['name' => 'ميناء القطيف (اختبار)']);
        $this->captain = Fisher::factory()->captain()->ownedBy($this->owner)->create(['name' => 'كابتن الواجهة', 'port_id' => $this->port->id]);
        $this->boat = Boat::factory()->ownedBy($this->owner)->captainedBy($this->captain)->create(['port_id' => $this->port->id, 'name' => 'قارب الواجهة']);

        $this->counter = User::factory()->role(Role::COUNTER)->create(['name' => 'عدّاد الواجهة']);
        StatisticsOfficer::factory()->forUser($this->counter)->atPort($this->port)->create();
    }

    private function as(User $user): static
    {
        // fresh(): المصنع لا يكتب locale فيبقى null في الذاكرة بينما القاعدة تعطيه 'ar'.
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user->fresh());

        return $this;
    }

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
                'recorded_at' => now()->toDateString(),
                'added_by' => $this->owner->id,
            ]);
        }

        return $trip;
    }

    public function test_counter_endpoints_refuse_other_roles_and_trips_of_other_ports(): void
    {
        $this->as($this->owner)->getJson('/api/v1/counter/dashboard')
            ->assertValidRequest()->assertValidResponse(403);
        $this->as(User::factory()->role(Role::DALAL)->create())->getJson('/api/v1/counter/trips')
            ->assertValidRequest()->assertValidResponse(403);

        $otherPort = Port::factory()->create();
        $otherBoat = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $otherPort->id]);
        $foreign = Trip::factory()->onBoat($otherBoat)->create(['return_port_id' => $otherPort->id, 'status' => Trip::AWAITING_COUNT]);

        $this->as($this->counter)->getJson("/api/v1/counter/trips/{$foreign->id}")
            ->assertValidRequest()->assertValidResponse(404);
        $this->as($this->counter)->postJson("/api/v1/counter/trips/{$foreign->id}/receive")
            ->assertValidRequest()->assertValidResponse(404);

        // رحلة في ميناء العدّاد لم تعد بعد ليست من شأنه.
        $atSea = Trip::factory()->onBoat($this->boat)->create(['status' => Trip::AT_SEA]);
        $this->as($this->counter)->getJson("/api/v1/counter/trips/{$atSea->id}")
            ->assertValidRequest()->assertValidResponse(404);
    }

    public function test_dashboard_and_trip_views_follow_the_app_screens(): void
    {
        $hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)']);
        $awaiting = $this->returnedTrip(['trip_number' => 'TR-2026-1201'], [['species' => $hamour, 'kg' => 120]]);
        $counting = $this->returnedTrip(['trip_number' => 'TR-2026-1202', 'status' => Trip::COUNTING, 'received_at' => now(), 'counter_id' => $this->counter->id]);
        $this->returnedTrip(['trip_number' => 'TR-2026-1203', 'status' => Trip::AWAITING_APPROVAL, 'counted_at' => now(), 'actual_weight_kg' => 90, 'diff_kg' => -10]);

        $this->as($this->counter)->getJson('/api/v1/counter/dashboard')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.port.name', 'ميناء القطيف (اختبار)')
            ->assertJsonPath('data.kpis.awaiting', 1)
            ->assertJsonPath('data.kpis.counting', 1)
            ->assertJsonPath('data.kpis.counted', 1)
            ->assertJsonPath('data.kpis.declared_kg', 120)
            ->assertJsonPath('data.kpis.diff_kg', -10)
            ->assertJsonPath('data.awaiting_trips.0.id', $awaiting->id)
            ->assertJsonPath('data.counting_trips.0.id', $counting->id)
            ->assertJsonCount(1, 'data.recent_trips');

        $this->as($this->counter)->getJson('/api/v1/counter/trips')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(3, 'data');
        $this->as($this->counter)->getJson('/api/v1/counter/trips?view=awaiting')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $awaiting->id);
        $this->as($this->counter)->getJson('/api/v1/counter/trips?view=counting')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $counting->id);
        $this->as($this->counter)->getJson('/api/v1/counter/trips?view=counted')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data');
        $this->as($this->counter)->getJson('/api/v1/counter/trips?search=1201')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_the_counter_receives_counts_and_adds_a_species_then_reads_the_report(): void
    {
        $hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)']);
        $shaari = Species::factory()->create(['name_ar' => 'الشعري (اختبار)']);
        $safi = Species::factory()->create(['name_ar' => 'الصافي (اختبار)']);

        $trip = $this->returnedTrip(['trip_number' => 'TR-2026-1300', 'license_number' => 'TL-7700'], [
            ['species' => $hamour, 'kg' => 100],
            ['species' => $shaari, 'kg' => 40],
        ]);

        // 1. الاستلام → تحت الإحصاء باسم العدّاد.
        $this->as($this->counter)->postJson("/api/v1/counter/trips/{$trip->id}/receive")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', Trip::COUNTING)
            ->assertJsonPath('data.progress_step', 3)
            ->assertJsonPath('data.counter.id', $this->counter->id);

        $this->as($this->counter)->postJson("/api/v1/counter/trips/{$trip->id}/receive")
            ->assertValidRequest()->assertValidResponse(422)
            ->assertJsonValidationErrorFor('status');

        // 2. العد: صنف مطابق، صنف مصحَّح، وصنف أضافه العدّاد.
        $this->as($this->counter)->postJson("/api/v1/counter/trips/{$trip->id}/count", ['items' => []])
            ->assertInvalidRequest()->assertValidResponse(422);

        $this->as($this->counter)->postJson("/api/v1/counter/trips/{$trip->id}/count", [
            'items' => [
                ['species_id' => $hamour->id, 'weight_kg' => 100],
                ['species_id' => $shaari->id, 'weight_kg' => 32.5, 'verified' => false, 'notes' => 'فرق في الوزن'],
                ['species_id' => $safi->id, 'weight_kg' => 12],
            ],
            'notes' => 'عُدّ على الميزان الثاني',
        ])->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', Trip::AWAITING_APPROVAL)
            ->assertJsonPath('data.progress_step', 4)
            ->assertJsonPath('data.actual_weight_kg', 144.5)
            ->assertJsonPath('data.diff_kg', 4.5)
            ->assertJsonPath('data.sale_status', Trip::SALE_OPEN)
            ->assertJsonCount(3, 'data.catch_records');

        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->owner->id, 'trip_id' => $trip->id, 'title' => 'اكتمل العد']);

        // 3. التقرير المفصّل.
        $this->as($this->counter)->getJson("/api/v1/counter/trips/{$trip->id}/report")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.trip.trip_number', 'TR-2026-1300')
            ->assertJsonPath('data.totals.species', 3)
            ->assertJsonPath('data.totals.counted_kg', 144.5)
            ->assertJsonPath('data.sections.0.title', 'الرحلة')
            ->assertJsonPath('data.catch.2.species', 'الصافي (اختبار)')
            ->assertJsonPath('data.catch.2.captain_kg', null)
            ->assertJsonPath('data.catch.2.added_by', 'عدّاد الواجهة');
    }

    public function test_the_catch_the_owner_submits_reaches_the_port_counter_as_a_notification(): void
    {
        $hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)']);
        $trip = Trip::factory()->onBoat($this->boat)->captainedBy($this->captain)->create([
            'trip_number' => 'TR-2026-1400',
            'return_port_id' => $this->port->id,
            'status' => Trip::AT_SEA,
            'started_at' => now()->subDay(),
        ]);

        $this->as($this->owner)->postJson("/api/v1/owner/trips/{$trip->id}/catch", [
            'items' => [['species_id' => $hamour->id, 'weight_kg' => 75]],
        ])->assertValidRequest()->assertValidResponse(200);

        $this->as($this->counter)->getJson('/api/v1/notifications')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'رحلة بانتظار العد')
            ->assertJsonPath('data.0.trip.trip_number', 'TR-2026-1400')
            ->assertJsonPath('meta.unread_count', 1);

        $this->as($this->counter)->getJson('/api/v1/counter/trips?view=awaiting')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.captain_input_kg', 75);
    }

    public function test_me_shows_the_counter_port_from_the_statistics_officer_record(): void
    {
        $this->as($this->counter)->getJson('/api/v1/auth/me')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.role.key', 'counter')
            ->assertJsonPath('data.port.name', 'ميناء القطيف (اختبار)')
            ->assertJsonPath('data.owner', null);
    }
}
