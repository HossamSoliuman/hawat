<?php

namespace Tests\Feature\Api;

use App\Models\Boat;
use App\Models\CustomerType;
use App\Models\Fisher;
use App\Models\IdempotencyKey;
use App\Models\IdType;
use App\Models\Port;
use App\Models\Role;
use App\Models\User;
use App\Services\Sms\ArraySmsSender;
use App\Services\Sms\SmsSender;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spectator\Spectator;
use Tests\TestCase;

/**
 * طلبات حوات 1 لإعداد القارب (CHANGELOG 1.6.0): تواريخ الإقامة والرخصة،
 * وصور الكابتن والطاقم، ودعوة الكابتن، ومنع التكرار، والتعديل المتزامن،
 * وعدد الطاقم المحسوب، وحالة "في البحر"، واكتمال الإعداد.
 */
class OwnerSetupApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Port $port;

    private ArraySmsSender $sms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');

        $this->sms = new ArraySmsSender;
        $this->app->instance(SmsSender::class, $this->sms);

        $this->owner = User::factory()->owner()->create();
        $this->port = Port::factory()->create(['name' => 'ميناء الإعداد (اختبار)']);
    }

    private function asOwner(?User $user = null): static
    {
        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user ?? $this->owner);

        return $this;
    }

    /**
     * الطلبات التالية بلا رمز (الدخول والتفعيل).
     */
    private function asGuest(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }

    private function boat(array $attributes = []): Boat
    {
        return Boat::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id] + $attributes);
    }

    private function crewPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'سالم البحار',
            'national_id' => '2234567890',
            'port_id' => $this->port->id,
        ];
    }

    private function captainPayload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'أحمد الشمري',
            'phone' => '0551112233',
            'national_id' => '1098765432',
            'port_id' => $this->port->id,
        ];
    }

    public function test_iqama_and_license_dates_are_saved_for_crew_and_captains(): void
    {
        $iqama = IdType::where('name', 'إقامة')->value('id');

        $crew = $this->asOwner()->postJson('/api/v1/owner/crew', $this->crewPayload([
            'id_type_id' => $iqama,
            'iqama_issue_date' => '2025-01-10',
            'iqama_expiry_date' => '2027-01-09',
            'license_number' => 'L-77',
            'license_date' => '2026-01-01',
            'license_expiry' => '2027-01-01',
        ]))->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.iqama_issue_date', '2025-01-10')
            ->assertJsonPath('data.iqama_expiry_date', '2027-01-09')
            ->assertJsonPath('data.license_date', '2026-01-01');

        $id = $crew->json('data.id');

        $this->asOwner()->getJson('/api/v1/owner/crew')
            ->assertValidResponse(200)
            ->assertJsonPath('data.0.iqama_expiry_date', '2027-01-09');

        $this->asOwner()->putJson("/api/v1/owner/crew/{$id}", $this->crewPayload(['iqama_issue_date' => '2026-05-01', 'iqama_expiry_date' => '2026-04-30']))
            ->assertValidResponse(422)
            ->assertJsonValidationErrors(['iqama_expiry_date' => 'تاريخ انتهاء الإقامة يجب أن يكون في تاريخ بدايتها أو بعده.']);

        // ملخص الرسالة بالعربية كذلك حين تتعدد الأخطاء.
        $this->asOwner()->postJson('/api/v1/owner/crew', [])
            ->assertValidResponse(422)
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'أخطاء أخرى'));

        $this->asOwner()->putJson("/api/v1/owner/crew/{$id}", $this->crewPayload(['license_date' => '2026-05-01', 'license_expiry' => '2026-01-01']))
            ->assertValidResponse(422)
            ->assertJsonValidationErrors(['license_expiry' => 'تاريخ انتهاء الرخصة يجب أن يكون في تاريخ بدايتها أو بعده.']);

        // التاريخان يُقبلان فارغين.
        $this->asOwner()->putJson("/api/v1/owner/crew/{$id}", $this->crewPayload(['iqama_issue_date' => null, 'iqama_expiry_date' => null]))
            ->assertValidResponse(200)
            ->assertJsonPath('data.iqama_issue_date', null);

        $captain = $this->asOwner()->postJson('/api/v1/owner/captains', $this->captainPayload([
            'password' => 'captain-123',
            'iqama_issue_date' => '2025-02-01',
            'iqama_expiry_date' => '2026-02-01',
            'license_date' => '2025-06-01',
            'license_expiry' => '2026-06-01',
        ]))->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.fisher.iqama_issue_date', '2025-02-01')
            ->assertJsonPath('data.fisher.license_date', '2025-06-01');

        $this->asOwner()->putJson('/api/v1/owner/captains/'.$captain->json('data.id'), $this->captainPayload(['iqama_issue_date' => '2025-02-01', 'iqama_expiry_date' => '2024-02-01']))
            ->assertValidResponse(422)
            ->assertJsonValidationErrors('iqama_expiry_date');

        // مالك آخر لا يرى الفرد ولا يعدّله.
        $stranger = User::factory()->owner()->create();
        $this->asOwner($stranger)->getJson("/api/v1/owner/crew/{$id}")->assertValidResponse(404);
        $this->asOwner($stranger)->putJson("/api/v1/owner/crew/{$id}", $this->crewPayload())->assertValidResponse(404);
    }

    public function test_owner_uploads_replaces_and_removes_captain_and_crew_photos(): void
    {
        Storage::fake('public');

        $fisher = Fisher::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id]);
        $captain = User::factory()->role(Role::CAPTAIN)->ownedBy($this->owner)->create();
        Fisher::factory()->ownedBy($this->owner)->forUser($captain)->create(['port_id' => $this->port->id]);
        $headers = ['Accept' => 'application/json', 'Content-Type' => 'multipart/form-data'];

        $this->asOwner()->post("/api/v1/owner/crew/{$fisher->id}/avatar", ['avatar' => UploadedFile::fake()->image('a.jpg', 512, 512)], $headers)
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.avatar_url', fn ($url) => str_contains($url, '/storage/avatars/crew/'));
        $first = $fisher->fresh()->avatar_path;
        Storage::disk('public')->assertExists($first);

        $this->asOwner()->post("/api/v1/owner/crew/{$fisher->id}/avatar", ['avatar' => UploadedFile::fake()->image('b.webp', 512, 512)], $headers)
            ->assertValidResponse(200);
        Storage::disk('public')->assertMissing($first);

        $this->asOwner()->getJson('/api/v1/owner/crew')
            ->assertJsonPath('data.0.avatar_url', fn ($url) => $url !== null);

        // مستند PDF أو صورة فوق 2MB مرفوضان على الحقل نفسه.
        $this->asOwner()->post("/api/v1/owner/crew/{$fisher->id}/avatar", ['avatar' => UploadedFile::fake()->create('iqama.pdf', 100, 'application/pdf')], $headers)
            ->assertValidResponse(422)->assertJsonValidationErrors('avatar');
        $this->asOwner()->post("/api/v1/owner/crew/{$fisher->id}/avatar", ['avatar' => UploadedFile::fake()->image('big.jpg', 600, 600)->size(3000)], $headers)
            ->assertValidResponse(422)->assertJsonValidationErrors('avatar');

        $this->asOwner()->deleteJson("/api/v1/owner/crew/{$fisher->id}/avatar")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.avatar_url', null);

        // صورة الكابتن صورة حسابه — يراها في تطبيقه.
        $this->asOwner()->post("/api/v1/owner/captains/{$captain->id}/avatar", ['avatar' => UploadedFile::fake()->image('c.png', 512, 512)], $headers)
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.avatar_url', fn ($url) => str_contains($url, '/storage/avatars/'))
            ->assertJsonPath('data.fisher.avatar_url', fn ($url) => $url !== null);
        $this->assertNotNull($captain->fresh()->avatar_path);

        $this->asOwner($captain->fresh())->getJson('/api/v1/auth/me')->assertJsonPath('data.avatar_url', fn ($url) => $url !== null);

        $this->asOwner()->deleteJson("/api/v1/owner/captains/{$captain->id}/avatar")->assertValidResponse(200)->assertJsonPath('data.avatar_url', null);

        // مالك آخر 404.
        $stranger = User::factory()->owner()->create();
        $this->asOwner($stranger)->post("/api/v1/owner/crew/{$fisher->id}/avatar", ['avatar' => UploadedFile::fake()->image('x.jpg')], $headers)->assertValidResponse(404);
        $this->asOwner($stranger)->post("/api/v1/owner/captains/{$captain->id}/avatar", ['avatar' => UploadedFile::fake()->image('x.jpg')], $headers)->assertValidResponse(404);
    }

    public function test_a_captain_created_without_a_password_is_invited_and_activates_with_the_texted_code(): void
    {
        $created = $this->asOwner()->postJson('/api/v1/owner/captains', $this->captainPayload())
            ->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.invitation.status', 'invited')
            ->assertJsonPath('data.invitation.activated_at', null);

        $captain = User::find($created->json('data.id'));
        $this->assertTrue($captain->isPendingInvitation());
        $this->assertNotNull($created->json('data.invitation.expires_at'));

        $message = (string) $this->sms->lastTo('0551112233');
        $this->assertStringContainsString($this->owner->name, $message);
        preg_match('/\d{6}/', $message, $m);
        $code = $m[0];

        // لا أحد يدخل بالحساب قبل التفعيل.
        $this->asGuest()->postJson('/api/v1/auth/login', ['phone' => '0551112233', 'password' => 'anything-123'])->assertStatus(422);

        $this->asGuest()->postJson('/api/v1/auth/activate', ['phone' => '0551112233', 'code' => '000000', 'password' => 'my-own-pass', 'password_confirmation' => 'my-own-pass'])
            ->assertValidRequest()->assertValidResponse(422)->assertJsonValidationErrors('code');

        $this->asGuest()->postJson('/api/v1/auth/activate', ['phone' => '0551112233', 'code' => $code, 'password' => 'my-own-pass', 'password_confirmation' => 'my-own-pass', 'device_name' => 'جوال الكابتن'])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.user.role.key', 'captain')
            ->assertJsonPath('data.token', fn ($token) => $token !== null);

        $this->asGuest()->postJson('/api/v1/auth/login', ['phone' => '0551112233', 'password' => 'my-own-pass'])->assertOk();

        $this->asOwner()->getJson("/api/v1/owner/captains/{$captain->id}")
            ->assertValidResponse(200)
            ->assertJsonPath('data.invitation.status', 'activated');

        // الرمز لمرة واحدة، وإعادة الإرسال لكابتن مفعّل مرفوضة.
        $this->asGuest()->postJson('/api/v1/auth/activate', ['phone' => '0551112233', 'code' => $code, 'password' => 'again-pass', 'password_confirmation' => 'again-pass'])->assertStatus(422);
        $this->asOwner()->postJson("/api/v1/owner/captains/{$captain->id}/invitation")->assertValidResponse(422);
    }

    public function test_an_invitation_expires_after_72_hours_and_the_owner_resends_it(): void
    {
        $created = $this->asOwner()->postJson('/api/v1/owner/captains', $this->captainPayload())->assertCreated();
        $id = $created->json('data.id');
        preg_match('/\d{6}/', (string) $this->sms->lastTo('0551112233'), $m);

        $this->travel(73)->hours();

        $this->asOwner()->getJson("/api/v1/owner/captains/{$id}")->assertJsonPath('data.invitation.status', 'expired');
        $this->asGuest()->postJson('/api/v1/auth/activate', ['phone' => '0551112233', 'code' => $m[0], 'password' => 'my-own-pass', 'password_confirmation' => 'my-own-pass'])
            ->assertStatus(422)->assertJsonValidationErrors(['code' => 'انتهت صلاحية الدعوة — اطلب من المالك إعادة إرسالها.']);

        $this->asOwner()->postJson("/api/v1/owner/captains/{$id}/invitation")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.invitation.status', 'invited');

        preg_match('/\d{6}/', (string) $this->sms->lastTo('0551112233'), $fresh);
        $this->asGuest()->postJson('/api/v1/auth/activate', ['phone' => '0551112233', 'code' => $fresh[0], 'password' => 'my-own-pass', 'password_confirmation' => 'my-own-pass'])->assertOk();
    }

    public function test_resetting_the_password_by_phone_code_activates_a_pending_invitation(): void
    {
        $id = $this->asOwner()->postJson('/api/v1/owner/captains', $this->captainPayload())->assertCreated()->json('data.id');

        $this->asGuest()->postJson('/api/v1/auth/forgot-password', ['phone' => '0551112233'])->assertOk();
        preg_match('/\d{6}/', (string) $this->sms->lastTo('0551112233'), $m);
        $this->asGuest()->postJson('/api/v1/auth/reset-password', ['phone' => '0551112233', 'code' => $m[0], 'password' => 'chosen-pass', 'password_confirmation' => 'chosen-pass'])->assertOk();

        $this->assertSame(User::INVITATION_ACTIVATED, User::find($id)->invitation_status);
        $this->asGuest()->postJson('/api/v1/auth/login', ['phone' => '0551112233', 'password' => 'chosen-pass'])->assertOk();
    }

    public function test_a_phone_registered_to_another_account_is_refused_on_the_phone_field(): void
    {
        User::factory()->dalal()->create(['phone' => '0551112233']);

        $this->asOwner()->postJson('/api/v1/owner/captains', $this->captainPayload())
            ->assertValidResponse(422)
            ->assertJsonValidationErrors(['phone' => 'رقم الجوال مسجّل لحساب آخر في حوات.']);

        $this->assertNull($this->sms->lastTo('0551112233'));
    }

    public function test_a_repeated_post_with_the_same_idempotency_key_creates_one_record(): void
    {
        $payload = ['name' => 'نجم الخليج', 'boat_number' => 'B-7001', 'port_id' => $this->port->id];

        $first = $this->asOwner()->postJson('/api/v1/owner/boats', $payload, ['Idempotency-Key' => 'boat-create-1'])
            ->assertValidRequest()->assertValidResponse(201);

        $second = $this->asOwner()->postJson('/api/v1/owner/boats', $payload, ['Idempotency-Key' => 'boat-create-1'])
            ->assertValidResponse(201)
            ->assertHeader('Idempotency-Replayed', 'true');

        $this->assertSame($first->json('data.id'), $second->json('data.id'));
        $this->assertSame($first->getContent(), $second->getContent(), 'الردّ المعاد هو الردّ الأول حرفًا بحرف');
        $this->assertSame(1, Boat::where('boat_number', 'B-7001')->count());

        // المفتاح نفسه لطلب مختلف مرفوض.
        $this->asOwner()->postJson('/api/v1/owner/boats', ['boat_number' => 'B-7002'] + $payload, ['Idempotency-Key' => 'boat-create-1'])
            ->assertValidResponse(422);

        // الردّ غير الناجح لا يُحفظ: التصحيح بالمفتاح نفسه يُنشئ.
        $this->asOwner()->postJson('/api/v1/owner/crew', ['name' => 'بلا هوية', 'port_id' => $this->port->id], ['Idempotency-Key' => 'crew-1'])->assertValidResponse(422);
        $this->assertSame(0, IdempotencyKey::where('key', 'crew-1')->count());
        $this->asOwner()->postJson('/api/v1/owner/crew', $this->crewPayload(), ['Idempotency-Key' => 'crew-1'])->assertValidResponse(201);
        $this->asOwner()->postJson('/api/v1/owner/crew', $this->crewPayload(), ['Idempotency-Key' => 'crew-1'])->assertValidResponse(201)->assertHeader('Idempotency-Replayed', 'true');
        $this->assertSame(1, Fisher::where('national_id', '2234567890')->count());

        // دعوة الكابتن لا تُرسل مرتين.
        $this->asOwner()->postJson('/api/v1/owner/captains', $this->captainPayload(), ['Idempotency-Key' => 'captain-1'])->assertValidResponse(201);
        $this->asOwner()->postJson('/api/v1/owner/captains', $this->captainPayload(), ['Idempotency-Key' => 'captain-1'])->assertValidResponse(201);
        $this->assertCount(1, $this->sms->sent);

        // المفتاح لكل حساب: مالك آخر بالمفتاح نفسه طلب مستقل.
        $other = User::factory()->owner()->create();
        $this->asOwner($other)->postJson('/api/v1/owner/boats', ['boat_number' => 'B-7003'] + $payload, ['Idempotency-Key' => 'boat-create-1'])
            ->assertValidResponse(201)->assertHeaderMissing('Idempotency-Replayed');

        // طلب أول ما زال يُنفَّذ → 409، وبعد دقيقة يُعدّ متعطّلًا فيكمل الطلب مكانه.
        $body = ['boat_number' => 'B-7004'] + $payload;
        IdempotencyKey::create(['user_id' => $this->owner->id, 'key' => 'in-flight', 'method' => 'POST', 'path' => 'api/v1/owner/boats', 'fingerprint' => hash('sha256', json_encode([$body, []]))]);
        $this->asOwner()->postJson('/api/v1/owner/boats', $body, ['Idempotency-Key' => 'in-flight'])->assertValidResponse(409);
        $this->travel(2)->minutes();
        $this->asOwner()->postJson('/api/v1/owner/boats', $body, ['Idempotency-Key' => 'in-flight'])->assertValidResponse(201);
        $this->assertSame(1, Boat::where('boat_number', 'B-7004')->count());
    }

    public function test_a_stale_version_gets_409_and_writes_nothing(): void
    {
        $boat = $this->boat(['name' => 'الأصل', 'boat_number' => 'B-1']);
        $payload = fn (string $name) => ['name' => $name, 'boat_number' => 'B-1', 'port_id' => $this->port->id];

        $read = $this->asOwner()->getJson("/api/v1/owner/boats/{$boat->id}")
            ->assertValidResponse(200)
            ->assertHeader('ETag', '"1"')
            ->assertJsonPath('data.version', 1);

        // الجهاز الأول يحفظ.
        $this->asOwner()->putJson("/api/v1/owner/boats/{$boat->id}", $payload('من الجهاز الأول'), ['If-Match' => $read->headers->get('ETag')])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertHeader('ETag', '"2"')
            ->assertJsonPath('data.version', 2);

        // الجهاز الثاني قرأ النسخة 1.
        $this->asOwner()->putJson("/api/v1/owner/boats/{$boat->id}", $payload('من الجهاز الثاني'), ['If-Match' => '"1"'])
            ->assertValidResponse(409)
            ->assertJsonPath('current_version', 2);
        $this->assertSame('من الجهاز الأول', $boat->fresh()->name);

        // حقل version بديل الترويسة، وبلا نسخة يُقبل كما كان.
        $this->asOwner()->putJson("/api/v1/owner/boats/{$boat->id}", $payload('قديم') + ['version' => 1])->assertValidResponse(409);
        $this->asOwner()->putJson("/api/v1/owner/boats/{$boat->id}", $payload('بلا نسخة'))->assertValidResponse(200)->assertJsonPath('data.version', 3);

        // فرد الطاقم.
        $fisher = Fisher::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id, 'national_id' => '2234567890']);
        $this->asOwner()->putJson("/api/v1/owner/crew/{$fisher->id}", $this->crewPayload(['name' => 'أول']), ['If-Match' => '1'])->assertValidResponse(200)->assertJsonPath('data.version', 2);
        $this->asOwner()->putJson("/api/v1/owner/crew/{$fisher->id}", $this->crewPayload(['name' => 'ثانٍ']), ['If-Match' => 'W/"1"'])->assertValidResponse(409);
        $this->assertSame('أول', $fisher->fresh()->name);

        // الكابتن: تعديل يمسّ الحساب وحده (الجوال) يرفع نسخته أيضًا.
        $captain = $this->asOwner()->postJson('/api/v1/owner/captains', $this->captainPayload(['password' => 'captain-123']))->assertCreated();
        $id = $captain->json('data.id');
        $version = $captain->json('data.version');
        $this->asOwner()->putJson("/api/v1/owner/captains/{$id}", $this->captainPayload(['phone' => '0551112299']), ['If-Match' => (string) $version])
            ->assertValidResponse(200)->assertJsonPath('data.version', $version + 1);
        $this->asOwner()->putJson("/api/v1/owner/captains/{$id}", $this->captainPayload(['name' => 'متأخر']), ['If-Match' => (string) $version])->assertValidResponse(409);
        $this->assertSame('أحمد الشمري', User::find($id)->name);
    }

    public function test_crew_count_is_derived_from_active_crew_and_capacity_is_in_tons(): void
    {
        $created = $this->asOwner()->postJson('/api/v1/owner/boats', [
            'name' => 'الحمولة', 'boat_number' => 'B-55', 'port_id' => $this->port->id,
            'capacity' => 2.5, 'crew_capacity' => 1, 'crew_count' => 9,
        ])->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.capacity', 2.5)
            ->assertJsonPath('data.crew_count', 0);
        $boat = Boat::find($created->json('data.id'));

        $first = $this->asOwner()->postJson('/api/v1/owner/crew', $this->crewPayload(['boat_id' => $boat->id]))->assertCreated();
        $this->asOwner()->postJson('/api/v1/owner/crew', $this->crewPayload(['national_id' => '2234567891', 'boat_id' => $boat->id]))->assertCreated();
        $this->assertSame(2, $boat->fresh()->crew_count);

        $this->asOwner()->getJson("/api/v1/owner/boats/{$boat->id}")
            ->assertJsonPath('data.crew_count', 2)
            ->assertJsonPath('data.setup.warnings.0.key', 'crew_over_capacity');

        // فرد معطّل أو منقول أو محذوف يخرج من العدد، والكابتن لا يُعدّ.
        $this->asOwner()->putJson('/api/v1/owner/crew/'.$first->json('data.id'), $this->crewPayload(['boat_id' => $boat->id, 'status' => 'غير نشط']))->assertOk();
        $this->assertSame(1, $boat->fresh()->crew_count);

        $this->asOwner()->postJson('/api/v1/owner/captains', $this->captainPayload(['password' => 'captain-123', 'boat_id' => $boat->id]))->assertCreated();
        $this->assertSame(1, $boat->fresh()->crew_count);

        $this->asOwner()->deleteJson('/api/v1/owner/crew/'.Fisher::where('national_id', '2234567891')->value('id'))->assertOk();
        $this->assertSame(0, $boat->fresh()->crew_count);
    }

    public function test_at_sea_is_set_only_by_trips_and_a_boat_at_sea_keeps_its_status(): void
    {
        $boat = $this->boat(['boat_number' => 'B-9', 'status' => 'نشط']);
        $form = ['name' => 'اسم جديد', 'boat_number' => 'B-9', 'port_id' => $this->port->id];

        $this->asOwner()->postJson('/api/v1/owner/boats', ['boat_number' => 'B-10', 'status' => 'في البحر'] + $form)
            ->assertValidResponse(422)->assertJsonValidationErrors('status');
        $this->asOwner()->putJson("/api/v1/owner/boats/{$boat->id}", ['status' => 'في البحر'] + $form)
            ->assertValidResponse(422)->assertJsonValidationErrors('status');

        $boat->update(['status' => Boat::AT_SEA]);

        // تعديل الاسم بلا حالة، أو بالحالة نفسها، لا يغيّرها.
        $this->asOwner()->putJson("/api/v1/owner/boats/{$boat->id}", $form)->assertValidResponse(200)->assertJsonPath('data.status', 'في البحر');
        $this->asOwner()->putJson("/api/v1/owner/boats/{$boat->id}", ['status' => 'في البحر'] + $form)->assertValidResponse(200);
        $this->asOwner()->putJson("/api/v1/owner/boats/{$boat->id}", ['status' => 'نشط'] + $form)
            ->assertValidResponse(422)->assertJsonValidationErrors(['status' => 'القارب في رحلة الآن — تتغيّر حالته حين تنتهي الرحلة.']);
        $this->assertSame('اسم جديد', $boat->fresh()->name);
        $this->assertSame('في البحر', $boat->fresh()->status);
    }

    public function test_setup_readiness_needs_an_active_boat_a_captain_and_crew(): void
    {
        $boat = $this->boat(['boat_number' => 'B-3', 'crew_count' => 0, 'license_expiry' => now()->subDay()]);

        $this->asOwner()->getJson("/api/v1/owner/boats/{$boat->id}")
            ->assertValidResponse(200)
            ->assertJsonPath('data.setup.ready', false)
            ->assertJsonPath('data.setup.missing.*.key', ['captain', 'crew'])
            ->assertJsonPath('data.setup.warnings.0.key', 'license_expired');

        $this->asOwner()->postJson('/api/v1/owner/captains', $this->captainPayload(['boat_id' => $boat->id]))->assertCreated();
        $this->asOwner()->postJson('/api/v1/owner/crew', $this->crewPayload(['boat_id' => $boat->id]))->assertCreated();

        $this->asOwner()->getJson("/api/v1/owner/boats/{$boat->id}")
            ->assertJsonPath('data.setup.ready', true)
            ->assertJsonPath('data.setup.warnings.*.key', ['captain_not_activated', 'license_expired']);

        $this->asOwner()->putJson("/api/v1/owner/boats/{$boat->id}", ['name' => 'صيانة', 'boat_number' => 'B-3', 'port_id' => $this->port->id, 'status' => 'صيانة'])
            ->assertJsonPath('data.setup.ready', false)
            ->assertJsonPath('data.setup.missing.0.key', 'status');
    }

    public function test_a_single_crew_member_is_readable_and_fish_market_customer_types_exist(): void
    {
        $fisher = Fisher::factory()->ownedBy($this->owner)->create(['port_id' => $this->port->id]);

        $this->asOwner()->getJson("/api/v1/owner/crew/{$fisher->id}")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertHeader('ETag', '"1"')
            ->assertJsonPath('data.id', $fisher->id);

        $captainFisher = Fisher::factory()->ownedBy($this->owner)->forUser(User::factory()->role(Role::CAPTAIN)->ownedBy($this->owner)->create())->create();
        $this->asOwner()->getJson("/api/v1/owner/crew/{$captainFisher->id}")->assertValidResponse(404);

        $types = $this->asOwner()->getJson('/api/v1/lookups')->assertValidResponse(200)->json('data.customer_types.*.name');
        $this->assertContains('سوق سمك', $types);
        $this->assertContains('محل سمك', $types);
        $this->assertSame(2, CustomerType::whereIn('name', ['سوق سمك', 'محل سمك'])->count());
    }
}
