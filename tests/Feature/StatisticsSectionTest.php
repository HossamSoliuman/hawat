<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\BycatchRecord;
use App\Models\CatchRecord;
use App\Models\Governorate;
use App\Models\Market;
use App\Models\MarketAuction;
use App\Models\Port;
use App\Models\Region;
use App\Models\Species;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\OnGovHost;
use Tests\TestCase;

/**
 * قسم الإحصاء — لوحاته التنفيذية ومخرجاته الورقية.
 *
 * الاختبارات هنا تحرس الحساب لا العرض: أن الكميات تُجمَّع من سجلات المصيد لا من
 * عدّادات مخزّنة، وأن نسبة الامتثال تُشتق من حالة الرحلة وفرقها، وأن التصدير
 * والطباعة يخرجان بالأرقام نفسها المعروضة على الشاشة.
 */
class StatisticsSectionTest extends TestCase
{
    use OnGovHost;
    use RefreshDatabase;

    private Port $gulfPort;

    private Port $redSeaPort;

    private Species $hamour;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedFisheries();
    }

    /*
    |--------------------------------------------------------------------------
    | موجز الإدارة العليا — رئيسة القسم
    |--------------------------------------------------------------------------
    */

    public function test_the_briefing_shows_the_certified_strategic_indicators(): void
    {
        $this->get('/stats')
            ->assertOk()
            ->assertSee('إجمالي المصيد المعتمد', false)
            ->assertSee('القوارب النشطة', false)
            ->assertSee('المخالفات المسجلة', false);
    }

    public function test_the_briefing_csv_carries_the_indicators_and_the_charts(): void
    {
        $csv = $this->get('/stats/export.csv')
            ->assertOk()
            ->assertDownload()
            ->streamedContent();

        $this->assertStringContainsString('total_approved_catch', $csv);
        $this->assertStringContainsString('by_region', $csv);
        $this->assertStringContainsString('fleet_status', $csv);
        // البادئة التي تجعل Excel يقرأ العناوين العربية كما هي.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function test_the_briefing_json_bundles_the_kpis_with_their_charts(): void
    {
        $payload = json_decode(
            $this->get('/stats/export.json')->assertOk()->streamedContent(),
            true,
        );

        $this->assertSame('موجز الإدارة العليا', $payload['meta']['page']);
        $this->assertCount(15, $payload['kpis']);
        $this->assertArrayHasKey('by_region', $payload['charts']);
        $this->assertArrayHasKey('monthly', $payload['charts']);
        $this->assertArrayHasKey('fleet_status', $payload['charts']);
    }

    /*
    |--------------------------------------------------------------------------
    | مقارنة الأداء
    |--------------------------------------------------------------------------
    */

    public function test_the_comparison_derives_compliance_from_the_trips_themselves(): void
    {
        // ميناء الخليج: رحلتان معتمدتان بلا فرق من أصل أربع ⇒ 50%.
        // ميناء البحر الأحمر: رحلة واحدة معتمدة بلا فرق من أصل واحدة ⇒ 100%.
        $this->get('/stats/performance-compare?view=port')
            ->assertOk()
            ->assertSee('50%', false)
            ->assertSee('100%', false);
    }

    public function test_the_comparison_ranks_by_catch_and_honours_the_top_limit(): void
    {
        $this->get('/stats/performance-compare?view=port&top=5')
            ->assertOk()
            ->assertSeeInOrder(['ميناء الدمام', 'ميناء جازان'], false);
    }

    public function test_the_comparison_groups_ports_under_their_governorate(): void
    {
        $this->get('/stats/performance-compare?view=governorate')
            ->assertOk()
            ->assertSee('القطيف', false)
            ->assertSee('جازان', false)
            ->assertDontSee('ميناء الدمام', false);
    }

    /*
    |--------------------------------------------------------------------------
    | التحليلات
    |--------------------------------------------------------------------------
    */

    public function test_analytics_compares_two_regions_and_reports_the_gap(): void
    {
        // 36 − 60 = −24 طنًا، أي −40% عن الأول.
        $this->get('/stats/analytics?type=region&first=المنطقة الشرقية&second=منطقة جازان')
            ->assertOk()
            ->assertSee('الفرق', false)
            ->assertSee('-24.0', false)
            ->assertSee('-40% مقارنة بالأول', false);
    }

    public function test_analytics_asks_for_a_selection_before_comparing(): void
    {
        $this->get('/stats/analytics')
            ->assertOk()
            ->assertSee('اختر عنصرين للمقارنة', false);
    }

    /*
    |--------------------------------------------------------------------------
    | حوات AI
    |--------------------------------------------------------------------------
    */

    public function test_the_assistant_answers_a_known_topic_from_the_data(): void
    {
        $this->get('/stats/ai-assistant?q='.urlencode('ما أكثر خمسة موانئ إنتاجًا؟'))
            ->assertOk()
            ->assertSee('الموانئ الأعلى إنتاجًا', false)
            ->assertSee('ميناء الدمام', false)
            ->assertDontSee('لم يطابق السؤال', false);
    }

    public function test_the_assistant_discloses_when_it_falls_back_to_the_summary(): void
    {
        $this->get('/stats/ai-assistant?q='.urlencode('كم عدد الطائرات؟'))
            ->assertOk()
            ->assertSee('الملخص التنفيذي لقطاع المصايد', false)
            ->assertSee('لم يطابق السؤال أيًا من الموضوعات المعروفة', false);
    }

    public function test_the_assistant_offers_suggestions_before_the_first_question(): void
    {
        $this->get('/stats/ai-assistant')
            ->assertOk()
            ->assertSee('أسئلة مقترحة', false)
            ->assertDontSee('HAWAT AI INSIGHT', false);
    }

    /*
    |--------------------------------------------------------------------------
    | التقارير
    |--------------------------------------------------------------------------
    */

    public function test_the_reports_page_lists_the_sixteen_reports_with_their_counts(): void
    {
        $this->get('/stats/reports')
            ->assertOk()
            ->assertSee('16 تقرير', false)
            ->assertSee('تقرير القوارب', false)
            ->assertSee('تقرير المخالفات', false);
    }

    public function test_a_report_exports_with_arabic_column_headers(): void
    {
        $csv = $this->get('/stats/reports/boats/export.csv')
            ->assertOk()
            ->assertDownload('boats-'.now()->toDateString().'.csv')
            ->streamedContent();

        $this->assertStringContainsString('اسم القارب', $csv);
        $this->assertStringContainsString('حالة الرخصة', $csv);
        $this->assertStringContainsString('نجم الخليج', $csv);
    }

    public function test_a_report_prints_outside_the_dashboard_layout(): void
    {
        $this->get('/stats/reports/ports/print')
            ->assertOk()
            ->assertSee('تقرير الموانئ', false)
            ->assertSee('ميناء الدمام', false)
            // صفحة الطباعة مستند وحده: لا قائمة جانبية معه.
            ->assertDontSee('sidebar-nav', false);
    }

    public function test_an_unknown_report_is_not_found(): void
    {
        $this->get('/stats/reports/ghost/export.csv')->assertNotFound();
        $this->get('/stats/reports/ghost/print')->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | تقارير الإنتاج
    |--------------------------------------------------------------------------
    */

    public function test_the_production_report_aggregates_the_selected_month(): void
    {
        $this->get('/stats/monthly-reports?period=monthly&year=2026&month=3')
            ->assertOk()
            ->assertSee('المنطقة الشرقية', false)
            ->assertSee('ميناء الدمام', false)
            ->assertSee('الهامور', false);
    }

    public function test_the_production_report_filters_by_region(): void
    {
        $this->get('/stats/monthly-reports?period=yearly&year=2026&region='.urlencode('منطقة جازان'))
            ->assertOk()
            ->assertSee('ميناء جازان', false)
            ->assertDontSee('ميناء الدمام', false);
    }

    public function test_the_production_report_says_so_when_the_period_is_empty(): void
    {
        $this->get('/stats/monthly-reports?period=yearly&year=2019')
            ->assertOk()
            ->assertSee('لا توجد بيانات لهذه الفترة', false);
    }

    public function test_the_production_report_exports_the_ports_breakdown(): void
    {
        $csv = $this->get('/stats/monthly-reports/export.csv?period=yearly&year=2026')
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('الميناء', $csv);
        $this->assertStringContainsString('ميناء الدمام', $csv);
        $this->assertStringContainsString('ميناء جازان', $csv);
    }

    public function test_the_production_report_prints_the_period_it_was_built_for(): void
    {
        $this->get('/stats/monthly-reports/print?period=monthly&year=2026&month=3')
            ->assertOk()
            ->assertSee('الشهري مارس 2026', false)
            ->assertDontSee('sidebar-nav', false);
    }

    /*
    |--------------------------------------------------------------------------
    | النشرة السنوية
    |--------------------------------------------------------------------------
    */

    public function test_the_bulletin_builds_all_sixteen_pages_for_the_year(): void
    {
        $response = $this->get('/stats/annual-bulletin?year=2026')->assertOk();

        foreach (['كلمة الإدارة', 'الملخص التنفيذي', 'التغطية الجغرافية للمصايد', 'الإنتاج السنوي',
            'الإنتاج حسب المنطقة', 'أنواع الأسماك والأحياء البحرية', 'الموانئ الأعلى إنتاجًا', 'القوارب',
            'الرحلات', 'المؤشرات الاقتصادية', 'مقارنة السنوات', 'خريطة كثافة الإنتاج',
            'الصيد العرضي والأحياء البحرية', 'الجداول الإحصائية', 'الملاحق والمنهجية'] as $page) {
            $response->assertSee($page, false);
        }
    }

    public function test_the_bulletin_reports_the_years_production_and_its_leaders(): void
    {
        // 12,000 + 8,000 + 5,000 كجم = 25 طنًا، أعلاها ميناء الدمام.
        $this->get('/stats/annual-bulletin?year=2026')
            ->assertOk()
            ->assertSee('25.0 طن', false)
            ->assertSee('ميناء الدمام', false)
            ->assertSee('الهامور', false);
    }

    public function test_the_bulletin_flags_a_year_without_records(): void
    {
        $this->get('/stats/annual-bulletin?year=2020')
            ->assertOk()
            ->assertSee('لا توجد سجلات مصيد لسنة 2020', false);
    }

    public function test_the_bulletin_prints_without_the_dashboard_chrome(): void
    {
        $this->get('/stats/annual-bulletin/print?year=2026')
            ->assertOk()
            ->assertSee('النشرة السنوية للمصايد البحرية', false)
            ->assertDontSee('sidebar-nav', false);
    }

    /*
    |--------------------------------------------------------------------------
    | التجهيز
    |--------------------------------------------------------------------------
    */

    /**
     * مصايد صغيرة كاملة السلسلة: منطقتان، ميناءان، خمس رحلات وسجلات مصيد لسنة 2026.
     */
    private function seedFisheries(): void
    {
        $east = Region::create(['name' => 'المنطقة الشرقية', 'code' => 'EST', 'total_catch_tons' => 60]);
        $jazan = Region::create(['name' => 'منطقة جازان', 'code' => 'JZN', 'total_catch_tons' => 36]);

        $qatif = Governorate::create(['region_id' => $east->id, 'name' => 'القطيف', 'code' => 'QTF']);
        $jazanCity = Governorate::create(['region_id' => $jazan->id, 'name' => 'جازان', 'code' => 'JZC']);

        $this->gulfPort = Port::create([
            'governorate_id' => $qatif->id, 'name' => 'ميناء الدمام', 'code' => 'DMM',
            'lat' => 26.42, 'lng' => 50.09, 'total_catch_tons' => 60,
            'monthly_trips' => 40, 'active_boats' => 12, 'boats_count' => 15,
        ]);

        $this->redSeaPort = Port::create([
            'governorate_id' => $jazanCity->id, 'name' => 'ميناء جازان', 'code' => 'JZP',
            'lat' => 16.89, 'lng' => 42.55, 'total_catch_tons' => 36,
            'monthly_trips' => 22, 'active_boats' => 7, 'boats_count' => 9,
        ]);

        $gulfBoat = Boat::create([
            'port_id' => $this->gulfPort->id, 'name' => 'نجم الخليج', 'boat_number' => 'B-001',
            'captain' => 'سعد الحربي', 'status' => 'نشط', 'license_status' => 'سارية',
        ]);

        $redSeaBoat = Boat::create([
            'port_id' => $this->redSeaPort->id, 'name' => 'درة فرسان', 'boat_number' => 'B-002',
            'captain' => 'ناصر الشهري', 'status' => 'نشط', 'license_status' => 'سارية',
        ]);

        $this->hamour = Species::create(['name_ar' => 'الهامور', 'name_sci' => 'Epinephelus', 'catch_kg' => 20000, 'trips_count' => 4]);
        $shaari = Species::create(['name_ar' => 'الشعري', 'name_sci' => 'Lethrinus', 'catch_kg' => 5000, 'trips_count' => 1]);

        // ميناء الدمام: أربع رحلات، اثنتان معتمدتان بلا فرق ⇒ امتثال 50%.
        $trips = [
            ['boat' => $gulfBoat, 'port' => $this->gulfPort, 'number' => 'TR-1', 'status' => 'معتمدة', 'diff' => 0, 'approved' => 12000],
            ['boat' => $gulfBoat, 'port' => $this->gulfPort, 'number' => 'TR-2', 'status' => 'معتمدة', 'diff' => 0, 'approved' => 8000],
            ['boat' => $gulfBoat, 'port' => $this->gulfPort, 'number' => 'TR-3', 'status' => 'معتمدة', 'diff' => 150, 'approved' => 4000],
            ['boat' => $gulfBoat, 'port' => $this->gulfPort, 'number' => 'TR-4', 'status' => 'بانتظار الإحصاء', 'diff' => null, 'approved' => null],
            ['boat' => $redSeaBoat, 'port' => $this->redSeaPort, 'number' => 'TR-5', 'status' => 'معتمدة', 'diff' => 0, 'approved' => 5000],
        ];

        $created = [];

        foreach ($trips as $row) {
            $created[$row['number']] = Trip::create([
                'trip_number' => $row['number'],
                'boat_id' => $row['boat']->id,
                'departure_port_id' => $row['port']->id,
                'captain_name' => $row['boat']->captain,
                'departure_time' => '2026-03-05 04:30:00',
                'return_time' => '2026-03-05 16:30:00',
                'duration_hours' => 12,
                'gear_type' => 'شبك خيشوم',
                'captain_input_kg' => 12500,
                'actual_weight_kg' => 12000,
                'diff_kg' => $row['diff'],
                'approved_kg' => $row['approved'],
                'status' => $row['status'],
            ]);
        }

        // 12,000 + 8,000 كجم في الشرقية، و5,000 كجم في جازان ⇒ 25 طنًا للسنة.
        CatchRecord::create(['trip_id' => $created['TR-1']->id, 'species_id' => $this->hamour->id, 'quantity_kg' => 12000, 'price_per_kg' => 30, 'recorded_at' => '2026-03-10']);
        CatchRecord::create(['trip_id' => $created['TR-2']->id, 'species_id' => $this->hamour->id, 'quantity_kg' => 8000, 'price_per_kg' => 28, 'recorded_at' => '2026-03-12']);
        CatchRecord::create(['trip_id' => $created['TR-5']->id, 'species_id' => $shaari->id, 'quantity_kg' => 5000, 'price_per_kg' => 22, 'recorded_at' => '2026-03-14']);

        BycatchRecord::create(['trip_id' => $created['TR-1']->id, 'species_name' => 'سلحفاة بحرية', 'quantity_kg' => 12, 'action_taken' => 'إعادة للبحر حية']);

        $market = Market::create(['name' => 'سوق الدمام المركزي', 'region' => 'المنطقة الشرقية', 'governorate' => 'القطيف']);
        MarketAuction::create([
            'market_id' => $market->id, 'species_id' => $this->hamour->id,
            'auction_date' => '2026-03-15', 'quantity_offered_kg' => 900,
            'quantity_sold_kg' => 800, 'avg_price_per_kg' => 32,
        ]);
    }
}
