<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * يمسح البيانات التجريبية ليبدأ العميل بإدخال بياناته الحقيقية.
 *
 * يبقى ما هو حقيقة ثابتة: المناطق والمحافظات والموانئ ومواقع الصيد، ودليل
 * الأنواع وأدوات الصيد، والقوائم المرجعية، والأدوار، وإعدادات النظام، وحسابات
 * الوزارة (بلا دور تطبيق، أو المدير العام). ويُمسح كل ما عداها: القوارب
 * والصيادون والرحلات والمصيد والمبيعات وحسابات التطبيق وكل ما تفرّع منها.
 * وتُصفَّر عدّادات النشاط المخزّنة على الجغرافيا والأنواع لأنها أرقام تجريبية.
 *
 * كل جدول في القاعدة مصنّف هنا صراحةً؛ والأمر يرفض العمل إن وجد جدولًا لم
 * يُصنَّف، حتى لا يُترك جدول جديد بلا قرار. وقبل المسح تُحفظ نسخة JSON من كل
 * صف سيُحذف في storage/app/private/backups.
 */
class PurgeDemoData extends Command
{
    use ConfirmableTrait;

    protected $signature = 'hawat:purge-demo-data
        {--dry-run : اعرض ما سيُحذف دون حذف}
        {--force : نفّذ في الإنتاج دون سؤال}';

    protected $description = 'مسح البيانات التجريبية والإبقاء على البيانات المرجعية';

    /** جداول تبقى كما هي (عدا عدّادات النشاط أدناه، والمستخدمين المفلترين). */
    public const KEEP = [
        // النظام
        'migrations', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
        // الجغرافيا
        'regions', 'governorates', 'ports', 'fishing_sites', 'markets',
        // الأدلة المرجعية
        'species', 'gear_types', 'roles', 'fisher_service_types', 'org_positions',
        // القوائم المرجعية لبوابات التطبيق (LookupSeeder)
        'boat_categories', 'boat_types', 'maintenance_types', 'trip_types', 'fisher_roles',
        'id_types', 'job_titles', 'payment_methods', 'payment_statuses', 'customer_types',
        'stock_movement_types', 'notification_types', 'dalal_worker_types', 'expense_groups',
        'expense_categories', 'asset_types', 'document_types', 'pay_types',
        // الإعدادات
        'integration_settings', 'ui_translations', 'notification_settings', 'user_permissions',
        // حوكمة البيانات: تعريفات لا سجلات
        'data_catalog_assets', 'data_lineage_edges', 'business_glossary_terms',
        'kpi_registries', 'fao_standard_mappings',
        // حسابات الوزارة تبقى، وحسابات التطبيق تُحذف (انظر purgeUsers)
        'users',
    ];

    /** جداول تُفرَّغ كاملًا. */
    public const PURGE = [
        // الجلسات والرموز
        'password_reset_tokens', 'sessions', 'personal_access_tokens', 'otp_codes',
        // الأسطول والمواسم
        'boats', 'fishers', 'statistics_officers', 'fishing_seasons', 'season_licenses',
        // العمليات
        'trips', 'catch_records', 'bycatch_records', 'alerts', 'violations', 'market_auctions',
        'audit_logs', 'data_quality_issues',
        // الإدارة الفرعية والخدمات
        'org_staff', 'admin_tasks', 'staff_notifications', 'fisher_service_staff',
        'fisher_service_staff_type', 'fisher_service_requests', 'support_tickets',
        'registration_requests', 'app_notifications',
        // بوابة المالك والدلال
        'boat_maintenances', 'owner_employees', 'customers', 'vendors', 'sales', 'sale_items',
        'consignments', 'consignment_items', 'stock_movements', 'dalal_profiles', 'dalal_workers',
        'dalal_partnerships', 'dalal_payouts', 'dalal_invoice_reviews', 'expenses',
        'fishing_equipment', 'fishing_equipment_season', 'assets', 'boat_inspections',
        'fleet_documents', 'payrolls', 'payroll_lines', 'crew_advances', 'month_closings',
        'month_closing_boats',
        // شركات التشغيل
        'operating_companies', 'operating_company_ports', 'hiring_rounds',
        'counter_applications', 'counter_transfers',
    ];

    /** عدّادات نشاط مخزّنة تُعاد إلى الصفر (أو null). */
    public const RESET_COUNTERS = [
        'regions' => ['total_catch_tons' => 0, 'active_boats' => 0, 'active_fishers' => 0],
        'governorates' => ['total_catch_tons' => 0, 'active_boats' => 0, 'active_fishers' => 0],
        'ports' => [
            'boats_count' => 0, 'active_boats' => 0, 'fishers_count' => 0, 'daily_trips' => 0,
            'monthly_trips' => 0, 'total_catch_tons' => 0, 'statistics_staff' => 0,
        ],
        'fishing_sites' => ['catch_kg' => 0, 'trips_count' => 0, 'boats_count' => 0, 'avg_catch_per_trip' => 0],
        'species' => ['catch_kg' => 0, 'trips_count' => 0, 'boats_count' => 0, 'top_port' => null],
    ];

    /** مجلدات المرفوعات على القرص العام، وكلها تخص سجلات تُحذف. */
    public const UPLOAD_DIRECTORIES = ['avatars', 'dalal-logos', 'expenses', 'inspections', 'documents'];

    public function handle(): int
    {
        $tables = collect(Schema::getTableListing(Schema::getCurrentSchemaName(), schemaQualified: false));
        $unknown = $tables->diff([...self::KEEP, ...self::PURGE])->values();

        if ($unknown->isNotEmpty()) {
            $this->error('جداول غير مصنّفة — أضفها إلى KEEP أو PURGE أولًا: '.$unknown->implode('، '));

            return self::FAILURE;
        }

        $purge = $tables->intersect(self::PURGE)->values();
        $users = $this->demoUsers();

        $this->table(['الجدول', 'الصفوف التي ستُحذف'], [
            ...$purge->map(fn ($t) => [$t, DB::table($t)->count()])->filter(fn ($row) => $row[1] > 0)->all(),
            ['users (حسابات التطبيق)', $users->count()],
        ]);

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        if (! $this->confirmToProceed('سيُحذف كل ما سبق نهائيًا', fn () => true)) {
            return self::FAILURE;
        }

        $this->info('النسخة الاحتياطية: '.$this->backup($purge->all(), $users->pluck('id')->all()));

        Schema::withoutForeignKeyConstraints(function () use ($purge, $users) {
            foreach ($purge as $table) {
                DB::table($table)->truncate();
            }

            DB::table('users')->whereIn('id', $users->pluck('id'))->delete();
        });

        DB::table('user_permissions')
            ->whereNotIn('user_email', DB::table('users')->whereNotNull('email')->select('email'))
            ->delete();

        foreach (self::RESET_COUNTERS as $table => $values) {
            DB::table($table)->update($values);
        }

        foreach (self::UPLOAD_DIRECTORIES as $directory) {
            Storage::disk('public')->deleteDirectory($directory);
        }

        Cache::flush();

        $this->info('تم مسح البيانات التجريبية. بقيت الحسابات: '.DB::table('users')->pluck('email')->implode('، '));

        return self::SUCCESS;
    }

    /**
     * حسابات التطبيق كلها تجريبية؛ تبقى حسابات الوزارة (بلا دور تطبيق) والمدير العام.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function demoUsers()
    {
        return DB::table('users')
            ->whereNotNull('role_id')
            ->where('role_id', '!=', Role::key(Role::SUPER_ADMIN)->id)
            ->get(['id', 'name', 'phone', 'email']);
    }

    /**
     * @param  list<string>  $tables
     * @param  list<int>  $userIds
     */
    private function backup(array $tables, array $userIds): string
    {
        $dump = [
            'created_at' => now()->toIso8601String(),
            'tables' => collect($tables)->mapWithKeys(fn ($t) => [$t => DB::table($t)->get()])->all(),
            'users' => DB::table('users')->whereIn('id', $userIds)->get(),
            'user_permissions' => DB::table('user_permissions')->get(),
            'counters' => collect(self::RESET_COUNTERS)
                ->mapWithKeys(fn ($values, $t) => [$t => DB::table($t)->get(['id', ...array_keys($values)])])
                ->all(),
        ];

        $path = 'backups/demo-purge-'.now()->format('Ymd-His').'.json.gz';
        Storage::disk('local')->put($path, gzencode(json_encode($dump, JSON_UNESCAPED_UNICODE)));

        return Storage::disk('local')->path($path);
    }
}
