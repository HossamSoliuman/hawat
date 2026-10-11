<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Boat;
use App\Models\Fisher;
use App\Models\Port;
use App\Models\Role;
use App\Models\Species;
use App\Models\Trip;
use App\Models\User;
use App\Services\Notifications\Notifier;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * بوابة الكابتن في /admin/captain/*: شاشات التطبيق نفسها على الويب — بانتظارك
 * (ابدأ / إلغاء بسبب)، النشطة (إنهاء وإرسال المخرجات)، القائمة، سجل الصيد،
 * الإشعارات، والملف الشخصي — والرحلة المسندة لغيره لا تُرى.
 */
class CaptainPortalTest extends TestCase
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

        $this->owner = User::factory()->owner()->create(['name' => 'مالك الاختبار']);
        $this->captain = User::factory()->role(Role::CAPTAIN)->ownedBy($this->owner)->create(['name' => 'كابتن الاختبار']);
        $this->port = Port::factory()->create(['name' => 'ميناء القطيف (اختبار)']);
        $this->boat = Boat::factory()->ownedBy($this->owner)->captainedBy($this->captain)->create(['port_id' => $this->port->id, 'name' => 'قارب الكابتن']);
    }

    private function asCaptain(): static
    {
        return $this->actingAs($this->captain);
    }

    private function trip(array $attributes = []): Trip
    {
        return Trip::factory()->onBoat($this->boat)->captainedBy($this->captain)->create($attributes);
    }

    public function test_captain_home_shows_pending_and_active_trips_with_the_captain_sidebar(): void
    {
        $pending = $this->trip(['trip_number' => 'TR-2026-0101']);
        $active = $this->trip(['trip_number' => 'TR-2026-0102', 'status' => Trip::AT_SEA, 'started_at' => now()->subDay()]);
        $this->boat->update(['status' => Trip::AT_SEA]);

        $this->asCaptain()->get('/admin')
            ->assertOk()
            ->assertSee('مرحبًا كابتن الاختبار')
            ->assertSee('الرحلات التي بانتظارك')
            ->assertSee('TR-2026-0101')
            ->assertSee('TR-2026-0102')
            ->assertSee(route('panel.captain.trips.start', $pending), false)
            ->assertSee(route('panel.captain.trips.show', $active), false)
            ->assertSee(route('panel.captain.catch-log'), false)
            ->assertSee(route('panel.notifications'), false)
            ->assertSee(route('panel.profile'), false)
            ->assertDontSee(route('panel.owner.trips'), false)
            ->assertDontSee(route('panel.users'), false);
    }

    public function test_captain_pages_are_for_captains_only_and_only_for_their_own_trips(): void
    {
        $otherCaptain = User::factory()->role(Role::CAPTAIN)->ownedBy($this->owner)->create();
        $foreign = Trip::factory()->onBoat($this->boat)->captainedBy($otherCaptain)->create();
        $mine = $this->trip();

        $this->actingAs($this->owner)->get('/admin/captain/trips')->assertForbidden();
        $this->actingAs(User::factory()->superAdmin()->create())->get('/admin/captain/trips')->assertForbidden();

        $this->asCaptain()->get('/admin/captain/trips')->assertOk()
            ->assertSee($mine->trip_number)
            ->assertDontSee($foreign->trip_number);
        $this->asCaptain()->get("/admin/captain/trips/{$foreign->id}")->assertNotFound();
        $this->asCaptain()->post("/admin/captain/trips/{$foreign->id}/start")->assertNotFound();
        $this->asCaptain()->get('/admin/owner/trips')->assertForbidden();
    }

    public function test_the_captain_runs_the_trip_from_its_page_and_the_owner_is_notified(): void
    {
        Storage::fake('public');
        $hamour = Species::factory()->create(['name_ar' => 'الهامور (اختبار)']);
        $shaari = Species::factory()->create(['name_ar' => 'الشعري (اختبار)']);
        $trip = $this->trip(['trip_number' => 'TR-2026-0200']);

        // بانتظارك: زر البدء وزر الإلغاء، ولا نموذج مخرجات بعد.
        $this->asCaptain()->get("/admin/captain/trips/{$trip->id}")
            ->assertOk()
            ->assertSee('ابدأ الرحلة')
            ->assertSee('إلغاء الرحلة')
            ->assertSee('تُسجَّل المخرجات بعد انطلاق الرحلة');

        // 1. البدء → في البحر، والقارب معه، والمالك يُبلَّغ.
        $this->asCaptain()->post("/admin/captain/trips/{$trip->id}/start")
            ->assertRedirect(route('panel.captain.trips.show', $trip));
        $this->assertSame(Trip::AT_SEA, $trip->fresh()->status);
        $this->assertSame(Trip::AT_SEA, $this->boat->fresh()->status);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->owner->id, 'trip_id' => $trip->id, 'title' => 'الرحلة قيد التنفيذ']);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $this->captain->id, 'trip_id' => $trip->id]);

        // لا بدء مرتين.
        $this->asCaptain()->from("/admin/captain/trips/{$trip->id}")->post("/admin/captain/trips/{$trip->id}/start")
            ->assertRedirect("/admin/captain/trips/{$trip->id}")
            ->assertSessionHasErrors('status');

        // 2. المخرجات: صنف واحد على الأقل.
        $this->asCaptain()->from("/admin/captain/trips/{$trip->id}")->post("/admin/captain/trips/{$trip->id}/catch", ['items' => []])
            ->assertSessionHasErrors('items');

        $this->asCaptain()->post("/admin/captain/trips/{$trip->id}/catch", ['items' => [
            ['species_id' => $hamour->id, 'weight_kg' => 100, 'notes' => 'صندوقان'],
            ['species_id' => $shaari->id, 'weight_kg' => 40.5],
        ]])->assertRedirect(route('panel.captain.trips.show', $trip));

        $trip->refresh();
        $this->assertSame(Trip::AWAITING_COUNT, $trip->status);
        $this->assertEqualsWithDelta(140.5, (float) $trip->captain_input_kg, 0.001);
        $this->assertSame($this->captain->id, $trip->catchRecords()->first()->added_by);
        $this->assertSame('نشط', $this->boat->fresh()->status);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->owner->id, 'trip_id' => $trip->id, 'title' => 'تم إرسال المخرجات']);

        // لا إلغاء بعد إرسال المخرجات.
        $this->asCaptain()->from("/admin/captain/trips/{$trip->id}")->post("/admin/captain/trips/{$trip->id}/cancel", ['reason' => 'متأخر'])
            ->assertSessionHasErrors('status');

        // 3. الوزارة تعدّ من صفحتها → الكابتن يُبلَّغ بالاكتمال والمالك بالعد.
        $this->post(route('stats.field-statistics.record', $trip->id), ['actual_weight_kg' => 138, 'statistics_officer' => 'محمد العوامي'])
            ->assertRedirect(route('stats.field-statistics'));
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->captain->id, 'trip_id' => $trip->id, 'title' => 'الرحلة مكتملة']);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->owner->id, 'trip_id' => $trip->id, 'title' => 'اكتمل العد']);

        // إعادة العد لا تكرّر الإشعار.
        $this->post(route('stats.field-statistics.record', $trip->id), ['actual_weight_kg' => 139, 'statistics_officer' => 'محمد العوامي']);
        $this->assertSame(1, AppNotification::forUser($this->captain)->where('title', 'الرحلة مكتملة')->count());

        // صفحة الرحلة تعرض المخرجات معلنةً ومعدودة، وسجل الصيد يجمعها.
        $this->asCaptain()->get("/admin/captain/trips/{$trip->id}")
            ->assertOk()
            ->assertSee('الهامور (اختبار)')
            ->assertSee('اكتمل العد')
            ->assertDontSee('ابدأ الرحلة');

        $this->asCaptain()->get('/admin/captain/catch-log')
            ->assertOk()
            ->assertSee('TR-2026-0200')
            ->assertSee('الشعري (اختبار)')
            ->assertSee('140.5');

        $this->asCaptain()->get('/admin/captain/catch-log?search=الشعري')
            ->assertOk()
            ->assertSee('الشعري (اختبار)')
            ->assertDontSee('صندوقان');
    }

    public function test_the_captain_cancels_a_pending_trip_with_a_reason(): void
    {
        $trip = $this->trip(['trip_number' => 'TR-2026-0300']);

        $this->asCaptain()->from("/admin/captain/trips/{$trip->id}")->post("/admin/captain/trips/{$trip->id}/cancel", [])
            ->assertRedirect("/admin/captain/trips/{$trip->id}")
            ->assertSessionHasErrors('reason');

        $this->asCaptain()->post("/admin/captain/trips/{$trip->id}/cancel", ['reason' => 'عطل في المحرك'])
            ->assertRedirect(route('panel.captain.trips.show', $trip));

        $this->assertSame(Trip::CANCELLED, $trip->fresh()->status);
        $this->assertSame('عطل في المحرك', $trip->fresh()->cancel_reason);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->owner->id, 'trip_id' => $trip->id, 'title' => 'تم إلغاء الرحلة']);

        $this->asCaptain()->get('/admin/captain/trips?view=cancelled')
            ->assertOk()
            ->assertSee('TR-2026-0300')
            ->assertSee('عطل في المحرك');
        $this->asCaptain()->get('/admin/captain/trips?view=pending')->assertOk()->assertDontSee('TR-2026-0300');
    }

    public function test_assigning_a_trip_notifies_the_captain_and_the_owner_acting_for_them_notifies_them_too(): void
    {
        $this->actingAs($this->owner)->post(route('panel.owner.trips.store'), ['boat_id' => $this->boat->id, 'planned_days' => 2])
            ->assertRedirect();

        $trip = Trip::forCaptain($this->captain)->first();
        $this->assertNotNull($trip);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->captain->id, 'trip_id' => $trip->id, 'title' => 'رحلة جديدة بانتظارك']);

        $this->actingAs($this->owner)->post(route('panel.owner.trips.start', $trip))->assertRedirect();
        $this->assertDatabaseHas('app_notifications', ['user_id' => $this->captain->id, 'trip_id' => $trip->id, 'title' => 'الرحلة قيد التنفيذ']);
        $this->assertDatabaseMissing('app_notifications', ['user_id' => $this->owner->id, 'trip_id' => $trip->id, 'title' => 'الرحلة قيد التنفيذ']);

        // نقل الرحلة لكابتن آخر يُبلّغه هو.
        $other = User::factory()->role(Role::CAPTAIN)->ownedBy($this->owner)->create();
        $scheduled = Trip::factory()->onBoat($this->boat)->captainedBy($this->captain)->create();
        $this->actingAs($this->owner)->put(route('panel.owner.trips.update', $scheduled), ['boat_id' => $this->boat->id, 'captain_id' => $other->id])->assertRedirect();
        $this->assertDatabaseHas('app_notifications', ['user_id' => $other->id, 'trip_id' => $scheduled->id, 'title' => 'رحلة جديدة بانتظارك']);
    }

    public function test_notifications_page_lists_marks_read_and_opens_the_trip(): void
    {
        $trip = $this->trip(['trip_number' => 'TR-2026-0400']);
        app(Notifier::class)->tripAssigned($trip->load(['captain', 'boat']));
        $stranger = AppNotification::factory()->to(User::factory()->owner()->create())->create(['title' => 'إشعار غريب']);
        $mine = AppNotification::forUser($this->captain)->first();

        $this->asCaptain()->get('/admin')->assertOk()->assertSee('topbar-bell')->assertSee('<span class="count">1</span>', false);

        $this->asCaptain()->get('/admin/notifications')
            ->assertOk()
            ->assertSee('رحلة جديدة بانتظارك')
            ->assertSee('TR-2026-0400')
            ->assertSee('notif-item is-unread', false)
            ->assertDontSee('إشعار غريب');

        $this->asCaptain()->post("/admin/notifications/{$stranger->id}/read")->assertNotFound();

        $this->asCaptain()->post("/admin/notifications/{$mine->id}/read")
            ->assertRedirect(route('panel.captain.trips.show', $trip));
        $this->assertNotNull($mine->fresh()->read_at);

        AppNotification::factory()->to($this->captain)->count(2)->create();
        $this->asCaptain()->post('/admin/notifications/read-all')->assertRedirect(route('panel.notifications'));
        $this->assertSame(0, AppNotification::forUser($this->captain)->unread()->count());
        $this->asCaptain()->get('/admin/notifications?filter=unread')->assertOk()->assertSee('لا إشعارات')->assertDontSee('notif-item is-unread', false);
    }

    public function test_profile_page_shows_the_port_and_updates_name_avatar_and_password(): void
    {
        Storage::fake('public');
        Fisher::factory()->ownedBy($this->owner)->forUser($this->captain)->create(['port_id' => $this->port->id]);

        $this->asCaptain()->get('/admin/profile')
            ->assertOk()
            ->assertSee('كابتن الاختبار')
            ->assertSee('ميناء القطيف (اختبار)')
            ->assertSee('الحساب مفعّل');

        $this->asCaptain()->put('/admin/profile', ['name' => 'كابتن معدَّل', 'email' => 'cap@example.com', 'locale' => 'en'])
            ->assertRedirect(route('panel.profile'));
        $this->assertSame('كابتن معدَّل', $this->captain->fresh()->name);
        $this->assertSame('en', $this->captain->fresh()->locale);

        $this->asCaptain()->post('/admin/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.png', 200, 200)])
            ->assertRedirect(route('panel.profile'));
        $path = $this->captain->fresh()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->asCaptain()->delete('/admin/profile/avatar')->assertRedirect(route('panel.profile'));
        $this->assertNull($this->captain->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($path);

        $this->asCaptain()->from('/admin/profile')->post('/admin/profile/password', ['current_password' => 'wrong', 'password' => 'new-secret-9', 'password_confirmation' => 'new-secret-9'])
            ->assertSessionHasErrors('current_password');

        $this->asCaptain()->post('/admin/profile/password', ['current_password' => 'password', 'password' => 'new-secret-9', 'password_confirmation' => 'new-secret-9'])
            ->assertRedirect(route('panel.profile'));

        $this->app['auth']->forgetGuards();
        $this->post(route('panel.login.store'), ['identifier' => $this->captain->phone, 'password' => 'new-secret-9'])->assertRedirect(route('panel.home'));
    }
}
