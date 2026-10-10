<?php

namespace Tests\Feature;

use App\Models\Fisher;
use App\Models\FisherServiceRequest;
use App\Models\FisherServiceStaff;
use App\Models\FisherServiceType;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\OnGovHost;
use Tests\TestCase;

/**
 * قسم الخدمات والتراخيص — طلباته وموظفوه وتذاكر دعمه.
 *
 * الاختبارات تحرس القواعد لا العرض: أن الاعتماد لا يسبق المعالجة، وأن الطلب
 * المغلق لا يُعاد فتحه، وأن الإسناد لا يقع على من لا يملك الصلاحية أو النطاق،
 * وأن التذكرة لا تُغلق بلا حلّ مكتوب.
 */
class ServicesSectionTest extends TestCase
{
    use OnGovHost;
    use RefreshDatabase;

    private Port $qatif;

    private Port $jazan;

    private Region $eastern;

    private Region $redSea;

    private FisherServiceType $renewal;

    private FisherServiceType $seasonal;

    private FisherServiceStaff $approver;

    private FisherServiceStaff $clerk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedGeography();
        $this->seedCatalogue();
    }

    /*
    |--------------------------------------------------------------------------
    | مسار الطلب
    |--------------------------------------------------------------------------
    */

    public function test_a_new_request_gets_the_next_number_in_sequence(): void
    {
        $this->submitRequest();
        $this->submitRequest();

        $this->assertSame(
            ['SR-0001', 'SR-0002'],
            FisherServiceRequest::orderBy('id')->pluck('request_number')->all()
        );
    }

    public function test_a_seasonal_service_is_refused_without_a_season(): void
    {
        $fisher = $this->fisher($this->qatif);

        $this->post('/services', [
            'fisher_service_type_id' => $this->seasonal->id,
            'fisher_name' => $fisher->name,
            'nationality_type' => 'سعودي',
            'priority' => 'عادية',
        ])->assertSessionHasErrors('fishing_season_id');

        $this->assertSame(0, FisherServiceRequest::count());
    }

    public function test_a_saudi_request_never_stores_a_conflicting_nationality(): void
    {
        $fisher = $this->fisher($this->qatif);

        $this->post('/services', [
            'fisher_service_type_id' => $this->renewal->id,
            'fisher_name' => $fisher->name,
            'nationality_type' => 'سعودي',
            'nationality' => 'مصري',
            'priority' => 'عادية',
        ])->assertRedirect();

        $this->assertSame('سعودي', FisherServiceRequest::first()->nationality);
    }

    public function test_processing_moves_the_request_and_stamps_the_processed_date(): void
    {
        $request = $this->request();

        $this->post("/services/{$request->id}/process", [
            'status' => 'بانتظار الاعتماد',
            'assigned_staff_id' => $this->clerk->id,
            'new_license_number' => 'LIC-9001',
            'new_license_expiry' => now()->addYear()->toDateString(),
        ])->assertSessionHasNoErrors();

        $request->refresh();

        $this->assertSame('بانتظار الاعتماد', $request->status);
        $this->assertSame($this->clerk->id, $request->assigned_staff_id);
        $this->assertSame(now()->toDateString(), $request->processed_date->toDateString());
    }

    public function test_a_processor_cannot_approve_through_the_processing_step(): void
    {
        // "معتمدة" ليست بين حالات المعالجة: الاعتماد قرار بتوقيع، لا خطوة في
        // قائمة الحالات.
        $request = $this->request();

        $this->post("/services/{$request->id}/process", ['status' => 'معتمدة'])
            ->assertSessionHasErrors('status');

        $this->assertSame('جديدة', $request->refresh()->status);
    }

    public function test_a_request_is_not_assigned_to_staff_without_the_permission(): void
    {
        $receptionist = FisherServiceStaff::create([
            'name' => 'فهد المستقبل', 'role' => 'مستقبل طلبات', 'section' => 'الخدمات والتراخيص',
            'can_create' => true, 'can_process' => false, 'active' => true,
        ]);

        $request = $this->request();

        $this->post("/services/{$request->id}/process", [
            'status' => 'قيد المعالجة',
            'assigned_staff_id' => $receptionist->id,
        ])->assertSessionHasErrors('assigned_staff_id');

        $this->assertNull($request->refresh()->assigned_staff_id);
    }

    public function test_a_request_is_not_assigned_to_staff_outside_its_geographic_scope(): void
    {
        $redSeaClerk = FisherServiceStaff::create([
            'name' => 'خالد البحر الأحمر', 'role' => 'معالج', 'section' => 'الخدمات والتراخيص',
            'assigned_region_id' => $this->redSea->id,
            'can_create' => true, 'can_process' => true, 'active' => true,
        ]);

        $request = $this->request();

        $this->post("/services/{$request->id}/process", [
            'status' => 'قيد المعالجة',
            'assigned_staff_id' => $redSeaClerk->id,
        ])->assertSessionHasErrors('assigned_staff_id');
    }

    public function test_a_request_is_not_assigned_to_staff_outside_its_service_authorisation(): void
    {
        $specialist = FisherServiceStaff::create([
            'name' => 'لمياء الموسمية', 'role' => 'معالج', 'section' => 'الخدمات والتراخيص',
            'can_create' => true, 'can_process' => true, 'active' => true,
        ]);
        $specialist->serviceTypes()->sync([$this->seasonal->id]);

        $request = $this->request();

        $this->post("/services/{$request->id}/process", [
            'status' => 'قيد المعالجة',
            'assigned_staff_id' => $specialist->id,
        ])->assertSessionHasErrors('assigned_staff_id');
    }

    public function test_approval_is_refused_before_processing_finishes(): void
    {
        $request = $this->request();

        $this->post("/services/{$request->id}/decide", [
            'decision' => 'اعتماد',
            'approved_by' => 'ماجد الغامدي',
        ])->assertSessionHasErrors('decision');

        $this->assertSame('جديدة', $request->refresh()->status);
    }

    public function test_approval_requires_a_signing_official(): void
    {
        $request = $this->request(['status' => 'بانتظار الاعتماد']);

        $this->post("/services/{$request->id}/decide", ['decision' => 'اعتماد'])
            ->assertSessionHasErrors('approved_by');
    }

    public function test_approval_issues_the_licence_and_records_the_signature(): void
    {
        $request = $this->request([
            'status' => 'بانتظار الاعتماد',
            'new_license_number' => 'LIC-9001',
            'new_license_expiry' => now()->addYear()->toDateString(),
        ]);

        $this->post("/services/{$request->id}/decide", [
            'decision' => 'اعتماد',
            'approved_by' => 'ماجد الغامدي',
            'note' => 'استُوفيت المستندات',
        ])->assertSessionHasNoErrors();

        $request->refresh();

        $this->assertSame('معتمدة', $request->status);
        $this->assertSame('ماجد الغامدي', $request->approved_by);
        $this->assertNotNull($request->approved_at);
        $this->assertStringContainsString('استُوفيت المستندات', $request->resolution);
    }

    public function test_a_closed_request_is_never_reopened(): void
    {
        $request = $this->request(['status' => 'معتمدة', 'approved_by' => 'ماجد الغامدي']);

        $this->post("/services/{$request->id}/process", ['status' => 'قيد المعالجة'])
            ->assertSessionHasErrors('status');

        $this->post("/services/{$request->id}/decide", [
            'decision' => 'رفض',
        ])->assertSessionHasErrors('status');

        $this->assertSame('معتمدة', $request->refresh()->status);
    }

    public function test_the_licence_card_prints_only_for_an_approved_request(): void
    {
        $pending = $this->request(['status' => 'بانتظار الاعتماد']);
        $this->get("/services/{$pending->id}/license")->assertNotFound();

        $approved = $this->request([
            'request_number' => 'SR-0900',
            'status' => 'معتمدة',
            'approved_by' => 'ماجد الغامدي',
            'new_license_number' => 'LIC-9001',
        ]);

        $this->get("/services/{$approved->id}/license")
            ->assertOk()
            ->assertSee('LIC-9001', false)
            ->assertSee('ماجد الغامدي', false);
    }

    public function test_the_list_search_matches_the_request_number_and_the_fisher(): void
    {
        $this->request(['request_number' => 'SR-0101']);
        $this->request(['request_number' => 'SR-0102', 'fisher_name' => 'عبدالله الحربي']);

        $this->get('/services?q=SR-0102')
            ->assertOk()
            ->assertSee('SR-0102', false)
            ->assertDontSee('SR-0101', false);
    }

    /*
    |--------------------------------------------------------------------------
    | لوحة الموظف ومساحتي
    |--------------------------------------------------------------------------
    */

    public function test_the_staff_dashboard_queues_follow_the_permissions(): void
    {
        $this->request(['request_number' => 'SR-0201', 'status' => 'بانتظار الاعتماد']);
        $this->request(['request_number' => 'SR-0202', 'status' => 'جديدة']);

        // المشرف يملك الاعتماد والمعالجة معًا، فيرى الطلبين.
        $this->get('/services/staff-dashboard?staff='.$this->approver->id)
            ->assertOk()
            ->assertSee('SR-0201', false)
            ->assertSee('SR-0202', false);

        // الكاتب بلا صلاحية اعتماد، فلا تظهر له قائمة الاعتماد ولا طلبها.
        $this->get('/services/staff-dashboard?staff='.$this->clerk->id)
            ->assertOk()
            ->assertDontSee('SR-0201', false)
            ->assertSee('SR-0202', false);
    }

    public function test_the_staff_dashboard_hides_requests_outside_the_geographic_scope(): void
    {
        // الكاتب مسند إلى المنطقة الشرقية، وطلب جازان في البحر الأحمر.
        $this->request(['request_number' => 'SR-0251', 'port_id' => $this->jazan->id]);
        $this->request(['request_number' => 'SR-0252', 'port_id' => $this->qatif->id]);

        $this->get('/services/staff-dashboard?staff='.$this->clerk->id)
            ->assertOk()
            ->assertDontSee('SR-0251', false)
            ->assertSee('SR-0252', false);
    }

    public function test_an_assigned_request_stays_out_of_another_processors_queue(): void
    {
        $other = FisherServiceStaff::create([
            'name' => 'سعد المعالج', 'role' => 'معالج', 'section' => 'الخدمات والتراخيص',
            'can_create' => true, 'can_process' => true, 'active' => true,
        ]);

        $this->request(['request_number' => 'SR-0301', 'assigned_staff_id' => $this->clerk->id]);

        $this->get('/services/staff-dashboard?staff='.$other->id)
            ->assertOk()
            ->assertDontSee('SR-0301', false);

        $this->get('/services/staff-dashboard?staff='.$this->clerk->id)
            ->assertOk()
            ->assertSee('SR-0301', false);
    }

    public function test_the_workspace_reports_the_staff_own_decisions_only(): void
    {
        $this->request([
            'request_number' => 'SR-0401', 'status' => 'معتمدة',
            'assigned_staff_id' => $this->clerk->id, 'approved_by' => $this->approver->name,
            'approved_at' => now()->subDay(),
        ]);
        $this->request([
            'request_number' => 'SR-0402', 'status' => 'معتمدة',
            'approved_by' => 'شخص آخر', 'approved_at' => now()->subDay(),
        ]);

        $this->get('/services/my-workspace?staff='.$this->approver->id.'&period=all')
            ->assertOk()
            ->assertSee('تقرير الأداء', false)
            // قرار واحد باسمه، لا اثنان.
            ->assertSee('(1/1)', false);
    }

    /*
    |--------------------------------------------------------------------------
    | إدارة الموظفين — انتقلت إلى إدارة النظام، فتُطلب بمستخدم داخل
    |--------------------------------------------------------------------------
    */

    public function test_an_empty_authorisation_means_every_service(): void
    {
        $this->actingAs(User::factory()->create())->post('/subadmin/staff-management', [
            'name' => 'موظف مفتوح التخويل',
            'role' => 'معالج',
            'section' => 'الخدمات والتراخيص',
            'all_services' => '1',
            'active' => '1',
            'can_process' => '1',
        ])->assertSessionHasNoErrors();

        $staff = FisherServiceStaff::where('name', 'موظف مفتوح التخويل')->firstOrFail();

        $this->assertCount(0, $staff->serviceTypes);
        $this->assertSame('الكل', $staff->handledServicesLabel());
        $this->assertTrue($staff->handles($this->request()));
    }

    public function test_a_staff_member_can_be_moved_between_sections(): void
    {
        $this->actingAs(User::factory()->create())->post("/subadmin/staff-management/{$this->clerk->id}/section", ['section' => 'الإحصاء'])
            ->assertSessionHasNoErrors();

        $this->assertSame('الإحصاء', $this->clerk->refresh()->section);
    }

    public function test_a_staff_member_with_assigned_requests_is_not_deleted(): void
    {
        $this->request(['assigned_staff_id' => $this->clerk->id]);

        $this->actingAs(User::factory()->create())->delete("/subadmin/staff-management/{$this->clerk->id}")
            ->assertSessionHasErrors('staff');

        $this->assertNotNull($this->clerk->fresh());
    }

    public function test_the_job_number_stays_unique_across_staff(): void
    {
        $this->actingAs(User::factory()->create())->post('/subadmin/staff-management', [
            'name' => 'مكرّر', 'role' => 'معالج', 'section' => 'الخدمات والتراخيص',
            'job_number' => $this->clerk->job_number,
        ])->assertSessionHasErrors('job_number');
    }

    /*
    |--------------------------------------------------------------------------
    | الدعم الفني
    |--------------------------------------------------------------------------
    */

    public function test_a_ticket_gets_the_next_number_and_opens_as_new(): void
    {
        $this->submitTicket()->assertSessionHasNoErrors();

        $ticket = SupportTicket::firstOrFail();

        $this->assertSame('TK-0001', $ticket->ticket_number);
        $this->assertSame('جديدة', $ticket->status);
        $this->assertNotNull($ticket->submitted_at);
    }

    public function test_assigning_a_new_ticket_starts_its_processing(): void
    {
        $this->submitTicket();
        $ticket = SupportTicket::firstOrFail();

        $this->post("/services/support/{$ticket->id}/assign", ['assigned_staff_id' => $this->clerk->id])
            ->assertSessionHasNoErrors();

        $ticket->refresh();

        $this->assertSame($this->clerk->id, $ticket->assigned_staff_id);
        $this->assertSame('قيد المعالجة', $ticket->status);
        $this->assertNotNull($ticket->assigned_at);
    }

    public function test_a_ticket_is_not_closed_without_a_written_resolution(): void
    {
        $this->submitTicket();
        $ticket = SupportTicket::firstOrFail();

        $this->post("/services/support/{$ticket->id}/resolve", ['status' => 'تم الحل'])
            ->assertSessionHasErrors('resolution');

        $this->assertSame('جديدة', $ticket->refresh()->status);

        $this->post("/services/support/{$ticket->id}/resolve", [
            'status' => 'تم الحل',
            'resolution' => 'صُحّح إعداد الميناء وأُعيد الحفظ بنجاح.',
        ])->assertSessionHasNoErrors();

        $ticket->refresh();

        $this->assertSame('تم الحل', $ticket->status);
        $this->assertNotNull($ticket->resolved_at);
    }

    /*
    |--------------------------------------------------------------------------
    | تجهيز البيانات
    |--------------------------------------------------------------------------
    */

    private function seedGeography(): void
    {
        $this->eastern = Region::create(['name' => 'المنطقة الشرقية (اختبار)']);
        $this->redSea = Region::create(['name' => 'منطقة البحر الأحمر (اختبار)']);

        $eastGov = Governorate::create(['region_id' => $this->eastern->id, 'name' => 'القطيف (اختبار)']);
        $redGov = Governorate::create(['region_id' => $this->redSea->id, 'name' => 'جازان (اختبار)']);

        $this->qatif = Port::create(['governorate_id' => $eastGov->id, 'name' => 'ميناء القطيف (اختبار)']);
        $this->jazan = Port::create(['governorate_id' => $redGov->id, 'name' => 'ميناء جازان (اختبار)']);
    }

    private function seedCatalogue(): void
    {
        $this->renewal = FisherServiceType::create([
            'name' => 'تجديد رخصة صيد حرفي', 'icon' => 'refresh-cw', 'issues_license' => true, 'display_order' => 1,
        ]);

        $this->seasonal = FisherServiceType::create([
            'name' => 'إصدار رخصة تصريح صيد موسمي', 'icon' => 'calendar',
            'requires_season' => true, 'issues_license' => true, 'display_order' => 2,
        ]);

        $this->approver = FisherServiceStaff::create([
            'name' => 'ماجد الغامدي', 'job_number' => 'SV-T001', 'role' => 'مشرف',
            'section' => 'الخدمات والتراخيص',
            'can_create' => true, 'can_process' => true, 'can_approve' => true, 'can_reject' => true, 'active' => true,
        ]);

        $this->clerk = FisherServiceStaff::create([
            'name' => 'عبدالرحمن الزهراني', 'job_number' => 'SV-T002', 'role' => 'معالج',
            'section' => 'الخدمات والتراخيص', 'assigned_region_id' => $this->eastern->id,
            'can_create' => true, 'can_process' => true, 'active' => true,
        ]);
    }

    private function fisher(Port $port): Fisher
    {
        return Fisher::create([
            'port_id' => $port->id,
            'name' => 'صياد '.$port->id.'-'.Fisher::count(),
            'national_id' => (string) (1000000000 + Fisher::count()),
            'license_number' => 'FL-'.Fisher::count(),
        ]);
    }

    private function submitRequest(array $overrides = [])
    {
        $fisher = $this->fisher($this->qatif);

        return $this->post('/services', array_merge([
            'fisher_service_type_id' => $this->renewal->id,
            'fisher_id' => $fisher->id,
            'fisher_name' => $fisher->name,
            'national_id' => $fisher->national_id,
            'port_id' => $this->qatif->id,
            'nationality_type' => 'سعودي',
            'priority' => 'عادية',
        ], $overrides));
    }

    /** طلب جاهز في قاعدة البيانات — يتخطّى النموذج ليبدأ الاختبار من الحالة المطلوبة. */
    private function request(array $overrides = []): FisherServiceRequest
    {
        $fisher = $this->fisher($this->qatif);

        return FisherServiceRequest::create(array_merge([
            'request_number' => 'SR-'.str_pad((string) (FisherServiceRequest::count() + 1), 4, '0', STR_PAD_LEFT),
            'fisher_service_type_id' => $this->renewal->id,
            'fisher_id' => $fisher->id,
            'fisher_name' => $fisher->name,
            'national_id' => $fisher->national_id,
            'port_id' => $this->qatif->id,
            'nationality_type' => 'سعودي',
            'priority' => 'عادية',
            'status' => 'جديدة',
            'submitted_date' => now()->subDays(2)->toDateString(),
        ], $overrides));
    }

    private function submitTicket(array $overrides = [])
    {
        return $this->post('/services/support', array_merge([
            'subject' => 'تعذّر حفظ سجل مصيد',
            'category' => 'مشكلة تقنية',
            'priority' => 'عاجلة',
            'module' => 'الإحصاء الميداني',
            'description' => 'تظهر رسالة خطأ عند حفظ وزن المصيد.',
            'submitted_by_name' => 'نورة المطيري',
            'submitted_by_email' => 'qatif@hawat.sa',
        ], $overrides));
    }
}
