<?php

namespace Tests\Feature\Api;

use App\Models\AppNotification;
use App\Models\Boat;
use App\Models\Fisher;
use App\Models\Port;
use App\Models\Role;
use App\Models\Species;
use App\Models\Trip;
use App\Models\User;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spectator\Spectator;
use Tests\TestCase;

/**
 * واجهة الكابتن /api/v1/captain/* والإشعارات /api/v1/notifications — كل ردّ
 * يُطابَق مع docs/api/openapi.yaml (Spectator).
 *
 * الكابتن يرى رحلاته المسندة إليه وحدها، ويبدؤها ويلغيها ويرسل مخرجاتها
 * بحسابه هو، ويصله إشعار بكل ما يخصّه.
 */
class CaptainApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $captain;

    private Boat $boat;

    private Port $port;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');

        $this->owner = User::factory()->owner()->create();
        $this->captain = User::factory()->role(Role::CAPTAIN)->ownedBy($this->owner)->create(['name' => 'كابتن الواجهة']);
        $this->port = Port::factory()->create(['name' => 'ميناء القطيف (اختبار)']);
        $this->boat = Boat::factory()->ownedBy($this->owner)->captainedBy($this->captain)->create(['port_id' => $this->port->id, 'name' => 'قارب الواجهة']);
    }

    private function as(User $user): static
    {
        // fresh(): المصنع لا يكتب locale فيبقى null في الذاكرة بينما القاعدة تعطيه 'ar'.
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user->fresh());

        return $this;
    }

    private function trip(array $attributes = []): Trip
    {
        return Trip::factory()->onBoat($this->boat)->captainedBy($this->captain)->create($attributes);
    }

    public function test_captain_endpoints_refuse_other_roles_and_foreign_trips(): void
    {
        $this->as($this->owner)->getJson('/api/v1/captain/dashboard')
            ->assertValidRequest()->assertValidResponse(403);

        $other = User::factory()->role(Role::CAPTAIN)->ownedBy($this->owner)->create();
        $foreign = Trip::factory()->onBoat($this->boat)->captainedBy($other)->create();

        $this->as($this->captain)->getJson("/api/v1/captain/trips/{$foreign->id}")
            ->assertValidRequest()->assertValidResponse(404);
        $this->as($this->captain)->postJson("/api/v1/captain/trips/{$foreign->id}/start")
            ->assertValidRequest()->assertValidResponse(404);
    }

    public function test_dashboard_and_trip_views_follow_the_app_screens(): void
    {
        $pending = $this->trip(['trip_number' => 'TR-2026-0501']);
        $active = $this->trip(['trip_number' => 'TR-2026-0502', 'status' => Trip::AT_SEA, 'started_at' => now()->subDay()]);
        $this->trip(['trip_number' => 'TR-2026-0503', 'status' => Trip::CANCELLED, 'cancel_reason' => 'طقس', 'cancelled_at' => now()]);
        Trip::factory()->onBoat($this->boat)->captainedBy(User::factory()->role(Role::CAPTAIN)->ownedBy($this->owner)->create())->create();

        $this->as($this->captain)->getJson('/api/v1/captain/dashboard')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.kpis.pending', 1)
            ->assertJsonPath('data.kpis.active', 1)
            ->assertJsonPath('data.kpis.cancelled', 1)
            ->assertJsonPath('data.kpis.total', 3)
            ->assertJsonPath('data.pending_trips.0.id', $pending->id)
            ->assertJsonPath('data.pending_trips.0.app_status', 'بانتظار الانطلاق')
            ->assertJsonPath('data.active_trips.0.id', $active->id)
            ->assertJsonCount(3, 'data.recent_trips');

        $this->as($this->captain)->getJson('/api/v1/captain/trips')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(3, 'data');
        $this->as($this->captain)->getJson('/api/v1/captain/trips?view=pending')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $pending->id);
        $this->as($this->captain)->getJson('/api/v1/captain/trips?view=active')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $active->id);
        $this->as($this->captain)->getJson('/api/v1/captain/trips?view=cancelled')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.cancel_reason', 'طقس');
        $this->as($this->captain)->getJson('/api/v1/captain/trips?search=0502')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_the_captain_starts_submits_the_catch_and_gets_notified_when_the_count_completes(): void
    {
        $hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)']);
        $shaari = Species::factory()->create(['name_ar' => 'الشعري (اختبار)']);
        $trip = $this->trip(['trip_number' => 'TR-2026-0600']);

        // 1. البدء → في البحر؛ المالك يُبلَّغ، الكابتن لا.
        $this->as($this->captain)->postJson("/api/v1/captain/trips/{$trip->id}/start")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', Trip::AT_SEA)
            ->assertJsonPath('data.progress_step', 1)
            ->assertJsonPath('data.captain.id', $this->captain->id);
        $this->assertSame(Trip::AT_SEA, $this->boat->fresh()->status);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->owner->id, 'trip_id' => $trip->id, 'title' => 'الرحلة قيد التنفيذ']);
        $this->assertSame(0, AppNotification::forUser($this->captain)->count());

        $this->as($this->captain)->postJson("/api/v1/captain/trips/{$trip->id}/start")
            ->assertValidRequest()->assertValidResponse(422)
            ->assertJsonValidationErrorFor('status');

        // 2. المخرجات → بانتظار الإحصاء، السطور بحساب الكابتن.
        $this->as($this->captain)->postJson("/api/v1/captain/trips/{$trip->id}/catch", ['items' => []])
            ->assertInvalidRequest()->assertValidResponse(422);

        $this->as($this->captain)->postJson("/api/v1/captain/trips/{$trip->id}/catch", ['items' => [
            ['species_id' => $hamour->id, 'weight_kg' => 100],
            ['species_id' => $shaari->id, 'weight_kg' => 40.5, 'notes' => 'صغير'],
        ]])->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', Trip::AWAITING_COUNT)
            ->assertJsonPath('data.progress_step', 2)
            ->assertJsonPath('data.captain_input_kg', 140.5)
            ->assertJsonCount(2, 'data.catch_records')
            ->assertJsonPath('data.catch_records.0.added_by', 'كابتن الواجهة');
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->owner->id, 'trip_id' => $trip->id, 'title' => 'تم إرسال المخرجات']);

        $this->as($this->captain)->postJson("/api/v1/captain/trips/{$trip->id}/cancel", ['reason' => 'متأخر'])
            ->assertValidRequest()->assertValidResponse(422);

        // 3. العد من صفحة الوزارة → الكابتن يُبلَّغ "الرحلة مكتملة".
        $this->app['auth']->forgetGuards();
        $this->post(route('stats.field-statistics.record', $trip->id), ['actual_weight_kg' => 138, 'statistics_officer' => 'محمد العوامي'])
            ->assertRedirect(route('stats.field-statistics'));

        $this->as($this->captain)->getJson("/api/v1/captain/trips/{$trip->id}")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.progress_step', 4)
            ->assertJsonPath('data.actual_weight_kg', 138)
            ->assertJsonPath('data.counter.name', 'محمد العوامي');

        // 4. سجل الصيد وملخصه.
        $this->as($this->captain)->getJson('/api/v1/captain/catch-log')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.trip.trip_number', 'TR-2026-0600');
        $this->as($this->captain)->getJson('/api/v1/captain/catch-log?search=الشعري')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.captain_notes', 'صغير');
        $this->as($this->captain)->getJson('/api/v1/captain/catch-log/summary')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.totals.captain_kg', 140.5)
            ->assertJsonPath('data.totals.species', 2)
            ->assertJsonPath('data.by_species.0.species', 'الهامور (اختبار)');

        // 5. الإشعارات: القائمة وعدّاد غير المقروء والتعليم.
        $list = $this->as($this->captain)->getJson('/api/v1/notifications')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'الرحلة مكتملة')
            ->assertJsonPath('data.0.trip.trip_number', 'TR-2026-0600')
            ->assertJsonPath('data.0.read', false)
            ->assertJsonPath('meta.unread_count', 1)
            ->json('data.0');

        $this->as($this->captain)->getJson('/api/v1/notifications/unread-count')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.unread_count', 1);

        $this->as($this->captain)->postJson("/api/v1/notifications/{$list['id']}/read")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.read', true);

        $ownerNotification = AppNotification::forUser($this->owner)->first();
        $this->as($this->captain)->postJson("/api/v1/notifications/{$ownerNotification->id}/read")
            ->assertValidRequest()->assertValidResponse(404);

        $this->as($this->owner)->getJson('/api/v1/notifications?unread=1')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(3, 'data');
        $this->as($this->owner)->postJson('/api/v1/notifications/read-all')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.marked', 3);
        $this->as($this->owner)->getJson('/api/v1/notifications/unread-count')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_the_captain_cancels_with_a_reason_and_the_assigned_captain_is_notified(): void
    {
        $trip = $this->trip();

        $this->as($this->captain)->postJson("/api/v1/captain/trips/{$trip->id}/cancel", [])
            ->assertInvalidRequest()->assertValidResponse(422);

        $this->as($this->captain)->postJson("/api/v1/captain/trips/{$trip->id}/cancel", ['reason' => 'عطل في المحرك'])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', Trip::CANCELLED)
            ->assertJsonPath('data.cancel_reason', 'عطل في المحرك');
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->owner->id, 'trip_id' => $trip->id, 'title' => 'تم إلغاء الرحلة']);

        // المالك يُسند رحلة من التطبيق → الكابتن يُبلَّغ.
        $this->as($this->owner)->postJson('/api/v1/owner/trips', ['boat_id' => $this->boat->id, 'planned_days' => 2])
            ->assertValidRequest()->assertValidResponse(201);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->captain->id, 'title' => 'رحلة جديدة بانتظارك']);
    }

    public function test_me_shows_the_captain_port_and_manages_the_avatar(): void
    {
        Storage::fake('public');
        Fisher::factory()->ownedBy($this->owner)->forUser($this->captain)->create(['port_id' => $this->port->id]);

        $this->as($this->captain)->getJson('/api/v1/auth/me')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.role.key', 'captain')
            ->assertJsonPath('data.port.name', 'ميناء القطيف (اختبار)')
            ->assertJsonPath('data.owner.id', $this->owner->id)
            ->assertJsonPath('data.avatar_url', null);

        $this->as($this->captain)->post('/api/v1/auth/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg', 300, 300)], ['Accept' => 'application/json', 'Content-Type' => 'multipart/form-data'])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.avatar_url', fn ($url) => str_contains($url, '/storage/avatars/'));
        $path = $this->captain->fresh()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->as($this->captain)->postJson('/api/v1/auth/avatar', [])
            ->assertValidResponse(422);

        $this->as($this->captain)->deleteJson('/api/v1/auth/avatar')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.avatar_url', null);
        Storage::disk('public')->assertMissing($path);
    }
}
