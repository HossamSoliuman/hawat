<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminTaskController;
use App\Http\Controllers\OrgStructureController;
use App\Models\AdminTask;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\BycatchRecord;
use App\Models\FishingSeason;
use App\Models\Governorate;
use App\Models\NotificationSetting;
use App\Models\OrgPosition;
use App\Models\OrgStaff;
use App\Models\Port;
use App\Models\Region;
use App\Models\StaffNotification;
use App\Models\Trip;
use App\Models\User;
use App\Models\UserPermission;
use App\Support\AlertGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Concerns\OnGovHost;
use Tests\TestCase;

/**
 * إدارة النظام على النطاق الرئيسي (/subadmin) — هيكله التنظيمي ومهامه وتنبيهاته
 * وإنذاراته.
 *
 * الاختبارات هنا تحرس القواعد لا العرض: أن الشجرة تبقى شجرة بعد حذف منصب أب،
 * وأن المهمة لا تُسند لمن لا يملك صلاحيتها، وأن الإنذار لا يُغلق بلا مسؤول،
 * وأن التوليد التلقائي يقرأ من البيانات ولا يكرّر ما ولّده.
 */
class SubAdministrationSectionTest extends TestCase
{
    use OnGovHost;
    use RefreshDatabase;

    private OrgPosition $directorate;

    private OrgStaff $approver;

    private OrgStaff $clerk;

    protected function setUp(): void
    {
        parent::setUp();

        // القسم نصف إدارة النظام، خلف دخول بوابة المعلومات.
        $this->actingAs(User::factory()->create());

        $this->seedOrganisation();
    }

    /*
    |--------------------------------------------------------------------------
    | الهيكل التنظيمي
    |--------------------------------------------------------------------------
    */

    public function test_the_tree_nests_each_position_under_its_parent(): void
    {
        $unit = OrgPosition::create([
            'title' => 'وحدة ميناء الدمام',
            'level' => 'مسؤول',
            'parent_id' => $this->directorate->id,
            'display_order' => 2,
        ]);

        $rows = collect(OrgStructureController::tree(
            OrgPosition::orderBy('display_order')->get()
        ));

        $parentRow = $rows->firstWhere(fn (array $row) => $row['position']->id === $this->directorate->id);
        $childRow = $rows->firstWhere(fn (array $row) => $row['position']->id === $unit->id);

        $this->assertSame(0, $parentRow['depth']);
        $this->assertSame(1, $childRow['depth']);
    }

    public function test_deleting_a_parent_promotes_its_children_to_roots(): void
    {
        $unit = OrgPosition::create([
            'title' => 'وحدة ميناء ينبع',
            'level' => 'مسؤول',
            'parent_id' => $this->directorate->id,
        ]);

        $this->delete(route('subadmin.org-structure.destroy', $this->directorate))
            ->assertRedirect(route('subadmin.org-structure'));

        // الحذف لا يبتلع الفروع: الوحدة تبقى وتصعد جذرًا مستقلًا.
        $this->assertDatabaseHas('org_positions', ['id' => $unit->id, 'parent_id' => null]);
    }

    public function test_a_position_cannot_become_its_own_parent(): void
    {
        $this->put(route('subadmin.org-structure.update', $this->directorate), [
            'title' => $this->directorate->title,
            'level' => 'مدير إدارة',
            'parent_id' => $this->directorate->id,
            'linked_role' => 'supervision',
            'scope_level' => 'kingdom',
            'active' => '1',
        ])->assertRedirect(route('subadmin.org-structure'));

        $this->assertNull($this->directorate->fresh()->parent_id);
    }

    public function test_the_structure_page_shows_each_position_with_its_staff(): void
    {
        $this->get('/subadmin/org-structure')
            ->assertOk()
            ->assertSee($this->directorate->title, false)
            ->assertSee($this->approver->name, false)
            ->assertSee($this->clerk->name, false);
    }

    public function test_staff_are_added_to_the_position_they_were_created_under(): void
    {
        $this->post(route('subadmin.org-structure.staff.store', $this->directorate), [
            'name' => 'بدر بن راشد العنزي',
            'job_number' => 'MF-9001',
            'rank' => 'الرتبة الرابعة',
            'status' => 'نشط',
            'can_process' => '1',
        ])->assertRedirect(route('subadmin.org-structure'));

        $this->assertDatabaseHas('org_staff', [
            'job_number' => 'MF-9001',
            'org_position_id' => $this->directorate->id,
            'can_process' => true,
            'can_approve' => false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تقويم المهام الإدارية
    |--------------------------------------------------------------------------
    */

    public function test_the_calendar_pads_the_month_until_its_first_weekday(): void
    {
        // مارس 2026 يبدأ يوم أحد (أول أعمدة الشبكة) ⇒ لا فراغات قبله، و31 يومًا.
        $cells = AdminTaskController::cells(Carbon::parse('2026-03-01'));

        $this->assertCount(31, $cells);
        $this->assertSame('2026-03-01', $cells[0]);

        // أبريل 2026 يبدأ يوم أربعاء ⇒ ثلاث خانات فارغة قبل أول يوم.
        $april = AdminTaskController::cells(Carbon::parse('2026-04-01'));

        $this->assertSame([null, null, null], array_slice($april, 0, 3));
        $this->assertSame('2026-04-01', $april[3]);
    }

    public function test_a_task_is_routed_to_the_section_its_type_belongs_to(): void
    {
        $this->post(route('subadmin.admin-tasks.store'), $this->taskPayload([
            'title' => 'إصدار رخص موسم الروبيان',
            'task_type' => 'إصدار رخصة',
            'required_permission' => 'إنشاء',
            'assigned_staff_id' => $this->clerk->id,
        ]))->assertRedirect();

        // القسم المختص يُشتق من النوع ولا يُدخل يدويًا.
        $this->assertDatabaseHas('admin_tasks', [
            'title' => 'إصدار رخص موسم الروبيان',
            'section' => 'الخدمات والتراخيص',
        ]);
    }

    public function test_a_task_is_not_assigned_to_staff_without_the_required_permission(): void
    {
        $this->post(route('subadmin.admin-tasks.store'), $this->taskPayload([
            'title' => 'اعتماد المصيد الشهري',
            'required_permission' => 'اعتماد',
            'assigned_staff_id' => $this->clerk->id,
        ]))->assertSessionHasErrors('assigned_staff_id');

        $this->assertDatabaseMissing('admin_tasks', ['title' => 'اعتماد المصيد الشهري']);
    }

    public function test_a_task_is_assigned_when_the_staff_holds_the_permission(): void
    {
        $this->post(route('subadmin.admin-tasks.store'), $this->taskPayload([
            'title' => 'اعتماد مصيد الدمام',
            'required_permission' => 'اعتماد',
            'assigned_staff_id' => $this->approver->id,
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('admin_tasks', [
            'title' => 'اعتماد مصيد الدمام',
            'assigned_staff_id' => $this->approver->id,
        ]);
    }

    public function test_completing_a_task_stamps_who_finished_it_and_when(): void
    {
        $task = $this->task(['status' => 'قيد التنفيذ']);

        $this->post(route('subadmin.admin-tasks.complete', $task))->assertRedirect();

        $task->refresh();

        $this->assertSame('مكتملة', $task->status);
        $this->assertNotNull($task->completed_at);
        $this->assertSame($this->approver->name, $task->completed_by);
    }

    public function test_an_unfinished_task_past_its_due_date_counts_as_overdue(): void
    {
        $this->task(['due_date' => Carbon::yesterday()->toDateString(), 'status' => 'مجدولة']);
        $this->task(['title' => 'مهمة منجزة', 'due_date' => Carbon::yesterday()->toDateString(), 'status' => 'مكتملة']);

        $overdue = AdminTask::all()->filter(fn (AdminTask $task) => $task->isOverdue());

        // المنجزة لا تتأخر مهما مضى استحقاقها.
        $this->assertCount(1, $overdue);
    }

    public function test_the_calendar_shows_the_tasks_of_the_month_it_is_asked_for(): void
    {
        $this->task(['title' => 'مهمة مارس', 'due_date' => '2026-03-12']);
        $this->task(['title' => 'مهمة أبريل', 'due_date' => '2026-04-12']);

        $this->get('/subadmin/admin-tasks?month=2026-03')
            ->assertOk()
            ->assertSee('مهمة مارس', false)
            ->assertDontSee('مهمة أبريل', false);
    }

    /*
    |--------------------------------------------------------------------------
    | التنبيهات الإدارية
    |--------------------------------------------------------------------------
    */

    public function test_the_notifications_page_opens_on_the_unread_ones(): void
    {
        $this->notification(['title' => 'طلب رخصة جديد', 'read' => false]);
        $this->notification(['title' => 'تنبيه مقروء', 'read' => true]);

        $this->get('/subadmin/staff-notifications')
            ->assertOk()
            ->assertSee('طلب رخصة جديد', false)
            ->assertDontSee('تنبيه مقروء', false);
    }

    public function test_marking_a_notification_read_records_the_moment(): void
    {
        $notification = $this->notification(['read' => false]);

        $this->post(route('subadmin.staff-notifications.read', $notification))->assertRedirect();

        $notification->refresh();

        $this->assertTrue($notification->read);
        $this->assertNotNull($notification->read_at);
    }

    public function test_marking_all_read_leaves_nothing_unread(): void
    {
        $this->notification(['title' => 'الأول', 'read' => false]);
        $this->notification(['title' => 'الثاني', 'read' => false]);

        $this->post(route('subadmin.staff-notifications.read-all'))->assertRedirect();

        $this->assertSame(0, StaffNotification::where('read', false)->count());
    }

    /*
    |--------------------------------------------------------------------------
    | مركز الإنذارات
    |--------------------------------------------------------------------------
    */

    public function test_the_generator_reads_the_alerts_out_of_the_data(): void
    {
        $this->seedFisheriesForAlerts();

        $result = AlertGenerator::run();

        $this->assertGreaterThan(0, $result['generated']);

        $types = Alert::pluck('type')->all();

        $this->assertContains('رخصة منتهية', $types);
        $this->assertContains('فرق مرتفع', $types);
        $this->assertContains('رحلة غير معتمدة', $types);
        $this->assertContains('اقتراب موسم صيد', $types);
        $this->assertContains('صيد عرضي لكائن حساس', $types);
    }

    public function test_regenerating_updates_the_existing_alerts_instead_of_duplicating_them(): void
    {
        $this->seedFisheriesForAlerts();

        AlertGenerator::run();
        $first = Alert::count();

        $second = AlertGenerator::run();

        $this->assertSame($first, Alert::count());
        $this->assertSame(0, $second['generated']);
    }

    public function test_the_generator_leaves_a_resolved_alert_closed(): void
    {
        $this->seedFisheriesForAlerts();

        AlertGenerator::run();

        $alert = Alert::where('type', 'رخصة منتهية')->firstOrFail();
        $alert->update(['assigned_to' => 'ريم العتيبي', 'status' => 'تم الحل', 'closed_at' => now()]);

        AlertGenerator::run();

        // إنذار عولج وأُغلق لا يُعاد فتحه من تشغيل تلقائي لاحق.
        $this->assertSame('تم الحل', $alert->fresh()->status);
    }

    public function test_assigning_a_responsible_moves_the_alert_into_processing(): void
    {
        UserPermission::create(['user_email' => 'reem@hawat.sa', 'full_name' => 'ريم العتيبي', 'role' => 'supervision', 'active' => true]);

        $alert = Alert::create(['title' => 'رخصة منتهية — نجم الخليج', 'type' => 'رخصة منتهية', 'severity' => 'مرتفع', 'status' => 'جديدة', 'date' => now()->toDateString()]);

        $this->post(route('subadmin.alerts.assign', $alert), ['assigned_to' => 'ريم العتيبي'])->assertRedirect();

        $alert->refresh();

        $this->assertSame('ريم العتيبي', $alert->assigned_to);
        $this->assertSame('قيد المعالجة', $alert->status);
        $this->assertNotNull($alert->assigned_at);
    }

    public function test_an_alert_without_a_responsible_cannot_be_closed(): void
    {
        $alert = Alert::create(['title' => 'فرق مرتفع في الرحلة TR-9', 'type' => 'فرق مرتفع', 'severity' => 'حرج', 'status' => 'جديدة', 'date' => now()->toDateString()]);

        $this->post(route('subadmin.alerts.resolve', $alert), ['resolution_note' => 'لا شيء'])
            ->assertRedirect()
            ->assertSessionHas('error');

        // الإغلاق شهادة بأن أحدًا تابع الإنذار، فلا يمرّ بلا مسؤول.
        $this->assertSame('جديدة', $alert->fresh()->status);
    }

    public function test_closing_an_assigned_alert_records_the_note_and_the_moment(): void
    {
        $alert = Alert::create([
            'title' => 'رحلة غير معتمدة — TR-7', 'type' => 'رحلة غير معتمدة', 'severity' => 'متوسط',
            'status' => 'قيد المعالجة', 'assigned_to' => 'ريم العتيبي', 'date' => now()->toDateString(),
        ]);

        $this->post(route('subadmin.alerts.resolve', $alert), ['resolution_note' => 'اعتُمدت الرحلة بعد المطابقة'])->assertRedirect();

        $alert->refresh();

        $this->assertSame('تم الحل', $alert->status);
        $this->assertSame('اعتُمدت الرحلة بعد المطابقة', $alert->resolution_note);
        $this->assertNotNull($alert->closed_at);
    }

    public function test_the_alerts_page_groups_by_severity_from_the_most_critical(): void
    {
        Alert::create(['title' => 'إنذار منخفض', 'type' => 'اقتراب موسم صيد', 'severity' => 'منخفض', 'status' => 'جديدة', 'date' => now()->toDateString()]);
        Alert::create(['title' => 'إنذار حرج', 'type' => 'فرق مرتفع', 'severity' => 'حرج', 'status' => 'جديدة', 'date' => now()->toDateString()]);

        $content = $this->get('/subadmin/alerts')->assertOk()->getContent();

        $this->assertLessThan(
            strpos($content, 'أولوية منخفضة'),
            strpos($content, 'أولوية حرجة'),
            'الإنذارات الحرجة يجب أن تتقدّم المنخفضة في العرض'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | المستخدمون وسجل العمليات والإعدادات
    |--------------------------------------------------------------------------
    */

    public function test_the_audit_log_filters_by_action(): void
    {
        AuditLog::create(['user_email' => 'admin@hawat.sa', 'action' => 'اعتماد', 'entity' => 'Trip', 'record_label' => 'TR-2026-0001']);
        AuditLog::create(['user_email' => 'admin@hawat.sa', 'action' => 'حذف', 'entity' => 'Boat', 'record_label' => 'B-777']);

        $this->get('/subadmin/audit-log?action=اعتماد')
            ->assertOk()
            ->assertSee('TR-2026-0001', false)
            ->assertDontSee('B-777', false);
    }

    public function test_saving_the_settings_disables_every_channel_that_was_not_checked(): void
    {
        NotificationSetting::create(['channel' => 'email', 'label' => 'إشعارات البريد الإلكتروني', 'enabled' => true]);
        NotificationSetting::create(['channel' => 'violations', 'label' => 'تنبيهات المخالفات', 'enabled' => true]);

        $this->put(route('subadmin.settings.update'), ['channels' => ['email' => '1']])
            ->assertRedirect(route('subadmin.settings'));

        // الخانة غير المؤشّرة لا تُرسل أصلًا، فالغائب يُطفأ لا يبقى على حاله.
        $this->assertTrue(NotificationSetting::where('channel', 'email')->value('enabled'));
        $this->assertFalse((bool) NotificationSetting::where('channel', 'violations')->value('enabled'));
    }

    /*
    |--------------------------------------------------------------------------
    | التجهيز
    |--------------------------------------------------------------------------
    */

    private function seedOrganisation(): void
    {
        $this->directorate = OrgPosition::create([
            'title' => 'إدارة الإحصاء والمعلومات',
            'level' => 'مدير إدارة',
            'linked_role' => 'supervision',
            'scope_level' => 'kingdom',
            'authorities' => 'اعتماد المصيد، مراجعة الفروقات',
            'display_order' => 1,
        ]);

        $this->approver = OrgStaff::create([
            'org_position_id' => $this->directorate->id,
            'name' => 'هند القحطاني',
            'job_number' => 'MF-1042',
            'email' => 'stats@hawat.sa',
            'status' => 'نشط',
            'can_create' => true,
            'can_process' => true,
            'can_approve' => true,
        ]);

        $this->clerk = OrgStaff::create([
            'org_position_id' => $this->directorate->id,
            'name' => 'نورة المطيري',
            'job_number' => 'MF-3055',
            'email' => 'qatif@hawat.sa',
            'status' => 'نشط',
            'can_create' => true,
            'can_process' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function taskPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'متابعة إدخال الرحلات',
            'org_position_id' => $this->directorate->id,
            'assigned_staff_id' => $this->approver->id,
            'required_permission' => 'أي صلاحية',
            'task_type' => 'متابعة',
            'priority' => 'عادية',
            'status' => 'مجدولة',
            'due_date' => Carbon::today()->toDateString(),
            'recurrence' => 'بدون',
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function task(array $overrides = []): AdminTask
    {
        return AdminTask::create(array_merge([
            'title' => 'متابعة إدخال الرحلات',
            'org_position_id' => $this->directorate->id,
            'assigned_staff_id' => $this->approver->id,
            'required_permission' => 'أي صلاحية',
            'task_type' => 'متابعة',
            'section' => 'الإدارة الفرعية',
            'priority' => 'عادية',
            'status' => 'مجدولة',
            'due_date' => Carbon::today()->toDateString(),
            'recurrence' => 'بدون',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function notification(array $overrides = []): StaffNotification
    {
        return StaffNotification::create(array_merge([
            'org_staff_id' => $this->approver->id,
            'recipient_email' => $this->approver->email,
            'recipient_name' => $this->approver->name,
            'title' => 'طلب رخصة جديد',
            'body' => 'وصل طلب رخصة يحتاج معالجتك.',
            'notification_type' => 'طلب جديد',
            'priority' => 'عادية',
            'read' => false,
        ], $overrides));
    }

    /**
     * بيانات تكفي لتشغيل كل قاعدة من قواعد توليد الإنذارات مرة واحدة.
     */
    private function seedFisheriesForAlerts(): void
    {
        $region = Region::create(['name' => 'المنطقة الشرقية', 'code' => 'EST']);
        $governorate = Governorate::create(['region_id' => $region->id, 'name' => 'القطيف', 'code' => 'QTF']);
        $port = Port::create(['governorate_id' => $governorate->id, 'name' => 'ميناء الدمام', 'code' => 'DMM']);

        $boat = Boat::create([
            'port_id' => $port->id, 'name' => 'نجم الخليج', 'boat_number' => 'B-001',
            'captain' => 'سعد الحربي', 'license_status' => 'سارية',
            'license_expiry' => Carbon::today()->subMonth()->toDateString(),
        ]);

        // فرق 20% بين إدخال الكابتن والوزن الفعلي، ورحلة عادت ولم تُعتمد منذ أسبوع.
        Trip::create([
            'trip_number' => 'TR-1', 'boat_id' => $boat->id, 'departure_port_id' => $port->id,
            'captain_input_kg' => 1000, 'actual_weight_kg' => 800, 'diff_kg' => -200,
            'return_time' => Carbon::today()->subDays(7), 'status' => 'بانتظار الاعتماد',
        ]);

        $approved = Trip::create([
            'trip_number' => 'TR-2', 'boat_id' => $boat->id, 'departure_port_id' => $port->id,
            'captain_input_kg' => 1000, 'actual_weight_kg' => 1000, 'diff_kg' => 0,
            'return_time' => Carbon::today()->subDay(), 'status' => 'معتمدة',
        ]);

        BycatchRecord::create(['trip_id' => $approved->id, 'species_name' => 'سلحفاة بحرية', 'quantity_kg' => 12, 'action_taken' => 'إعادة للبحر حية']);

        FishingSeason::create([
            'name' => 'موسم الروبيان', 'species' => 'الروبيان', 'region' => 'المنطقة الشرقية',
            'start_date' => Carbon::today()->addDays(5)->toDateString(),
            'end_date' => Carbon::today()->addMonths(4)->toDateString(),
        ]);
    }
}
