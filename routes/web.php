<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminResourceController;
use App\Http\Controllers\AdminTaskController;
use App\Http\Controllers\AiAssistantController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AnnualBulletinController;
use App\Http\Controllers\ApprovedCatchController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BoatController;
use App\Http\Controllers\BoatTimelineController;
use App\Http\Controllers\BycatchController;
use App\Http\Controllers\CatchTraceController;
use App\Http\Controllers\ComplianceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscrepancyReviewController;
use App\Http\Controllers\ExecutiveBriefingController;
use App\Http\Controllers\FieldStatisticsController;
use App\Http\Controllers\FisherController;
use App\Http\Controllers\FisherServiceRequestController;
use App\Http\Controllers\FishingSeasonController;
use App\Http\Controllers\FishingSiteController;
use App\Http\Controllers\FoodSecurityController;
use App\Http\Controllers\GovernorateController;
use App\Http\Controllers\IntegrationSettingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\MonthlyReportsController;
use App\Http\Controllers\MyWorkspaceController;
use App\Http\Controllers\NationalIndicatorsController;
use App\Http\Controllers\OrgStructureController;
use App\Http\Controllers\Panel\Counter\TripController as CounterTripController;
use App\Http\Controllers\Panel\Dalal\CustomerController as DalalCustomerController;
use App\Http\Controllers\Panel\Dalal\OwnerController as DalalOwnerController;
use App\Http\Controllers\Panel\Dalal\PartnershipController as DalalPartnershipController;
use App\Http\Controllers\Panel\Dalal\ReportController as DalalReportController;
use App\Http\Controllers\Panel\Dalal\SaleController as DalalSaleController;
use App\Http\Controllers\Panel\Dalal\SettingsController as DalalSettingsController;
use App\Http\Controllers\Panel\Dalal\StockController as DalalStockController;
use App\Http\Controllers\Panel\HomeController as PanelHomeController;
use App\Http\Controllers\Panel\LoginController as PanelLoginController;
use App\Http\Controllers\Panel\NotificationController as PanelNotificationController;
use App\Http\Controllers\Panel\Owner\AdvanceController as OwnerAdvanceController;
use App\Http\Controllers\Panel\Owner\AssetController as OwnerAssetController;
use App\Http\Controllers\Panel\Owner\BoatController as OwnerBoatController;
use App\Http\Controllers\Panel\Owner\CaptainController as OwnerCaptainController;
use App\Http\Controllers\Panel\Owner\CatchLogController as OwnerCatchLogController;
use App\Http\Controllers\Panel\Owner\ConsignmentController as OwnerConsignmentController;
use App\Http\Controllers\Panel\Owner\CrewController as OwnerCrewController;
use App\Http\Controllers\Panel\Owner\CrewPayController as OwnerCrewPayController;
use App\Http\Controllers\Panel\Owner\CustomerController as OwnerCustomerController;
use App\Http\Controllers\Panel\Owner\DalalAccountController as OwnerDalalAccountController;
use App\Http\Controllers\Panel\Owner\DalalController as OwnerDalalController;
use App\Http\Controllers\Panel\Owner\DalalInvoiceController as OwnerDalalInvoiceController;
use App\Http\Controllers\Panel\Owner\DalalPerformanceController as OwnerDalalPerformanceController;
use App\Http\Controllers\Panel\Owner\DalalStockController as OwnerDalalStockController;
use App\Http\Controllers\Panel\Owner\DocumentController as OwnerDocumentController;
use App\Http\Controllers\Panel\Owner\EmployeeController as OwnerEmployeeController;
use App\Http\Controllers\Panel\Owner\EquipmentController as OwnerEquipmentController;
use App\Http\Controllers\Panel\Owner\ExpenseController as OwnerExpenseController;
use App\Http\Controllers\Panel\Owner\InspectionController as OwnerInspectionController;
use App\Http\Controllers\Panel\Owner\MaintenanceController as OwnerMaintenanceController;
use App\Http\Controllers\Panel\Owner\MonthClosingController as OwnerMonthClosingController;
use App\Http\Controllers\Panel\Owner\PayrollController as OwnerPayrollController;
use App\Http\Controllers\Panel\Owner\ReportController as OwnerReportController;
use App\Http\Controllers\Panel\Owner\SaleController as OwnerSaleController;
use App\Http\Controllers\Panel\Owner\TripController as OwnerTripController;
use App\Http\Controllers\Panel\Owner\VendorController as OwnerVendorController;
use App\Http\Controllers\Panel\ProfileController as PanelProfileController;
use App\Http\Controllers\Panel\RegistrationRequestController as PanelRegistrationController;
use App\Http\Controllers\Panel\Company\ApplicationController as CompanyApplicationController;
use App\Http\Controllers\Panel\Company\CounterController as CompanyCounterController;
use App\Http\Controllers\Panel\Company\HiringRoundController as CompanyHiringRoundController;
use App\Http\Controllers\Panel\CompanyController as PanelCompanyController;
use App\Http\Controllers\Panel\CounterController as PanelCounterController;
use App\Http\Controllers\Panel\UserController as PanelUserController;
use App\Http\Controllers\PerformanceCompareController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\CounterApplicationController;
use App\Http\Controllers\RegistrationRequestController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SeaMapController;
use App\Http\Controllers\SeasonLicenseController;
use App\Http\Controllers\ServiceStaffController;
use App\Http\Controllers\ServiceStaffDashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SpeciesController;
use App\Http\Controllers\StaffNotificationController;
use App\Http\Controllers\StatisticsOfficerController;
use App\Http\Controllers\SupplyChainController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\SustainabilityController;
use App\Http\Controllers\TripController;
use App\Services\Owner\OwnerReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * بوابة المعلومات — نصف "إدارة النظام" الذي يحرّر البيانات الأساسية عبر
 * تبويبات config/info.php. تُسجَّل تحت /subadmin مع نصفه الآخر خلف دخول واحد،
 * والقيد على {tab} يمنعها من ابتلاع لوحات القسم ذات الأسماء الثابتة.
 */
$infoPortal = function (): void {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');

    // تبويب مجهول يُردّ بـ 404 من الموجّه بدل أن يصل إلى السجل فيرمي استثناءً.
    Route::name('admin.')->whereIn('tab', array_keys(config('info.tabs')))->group(function (): void {
        Route::get('{tab}', [AdminController::class, 'show'])->name('tab');

        Route::post('{tab}/{resource}', [AdminResourceController::class, 'store'])->name('resource.store');
        Route::put('{tab}/{resource}/{id}', [AdminResourceController::class, 'update'])->name('resource.update');
        Route::delete('{tab}/{resource}/{id}', [AdminResourceController::class, 'destroy'])->name('resource.destroy');

        Route::put('{tab}/integration/{provider}', [IntegrationSettingController::class, 'update'])->name('integration.update');
    });
};

/*
 * شاشة العرض — لوحات القاعة تحت البادئة /screens (كانت /gov)، وهي تبويب واحد في
 * قائمة الإحصاء (مربّعاتها من config/hawat.php → nav_screens).
 */
$screens = function (): void {
    // الرئيسة شاشة اختيار بمربّعات كبيرة (تُعرض على شاشة قاعة)، ولوحة المؤشرات
    // التي كانت عليها انتقلت إلى /screens/overview لتُفتح من مربّعها بملء الشاشة.
    Route::get('/', [DashboardController::class, 'screens'])->name('home');
    Route::get('/overview', [DashboardController::class, 'index'])->name('overview');
    Route::get('/sea-map', [SeaMapController::class, 'index'])->name('sea-map');

    Route::get('/production', [ProductionController::class, 'index'])->name('production');
    Route::get('/ports-compare', [PortController::class, 'compare'])->name('ports-compare');

    Route::get('/sustainability', [SustainabilityController::class, 'index'])->name('sustainability');
};

/*
 * إدارة النظام — نصفاها تحت البادئة /subadmin: هذا النصف ثم بوابة المعلومات.
 * قائمتهما واحدة (config/hawat.php → nav_subadmin) ودخولهما واحد على /login.
 *
 * لوحات هذا النصف تدير القطاع نفسه لا بياناته: الهيكل التنظيمي والموظفون، ثم
 * المهام والتنبيهات والإنذارات، ثم سجل العمليات والإعدادات. ورئيسة القسم كلّه
 * (/subadmin) نظرة عامة من بوابة المعلومات.
 */
$subAdministration = function (): void {
    Route::get('/org-structure', [OrgStructureController::class, 'index'])->name('org-structure');
    Route::post('/org-structure', [OrgStructureController::class, 'store'])->name('org-structure.store');
    Route::put('/org-structure/{position}', [OrgStructureController::class, 'update'])->name('org-structure.update');
    Route::delete('/org-structure/{position}', [OrgStructureController::class, 'destroy'])->name('org-structure.destroy');

    Route::post('/org-structure/{position}/staff', [OrgStructureController::class, 'storeStaff'])->name('org-structure.staff.store');
    Route::put('/org-structure/staff/{staff}', [OrgStructureController::class, 'updateStaff'])->name('org-structure.staff.update');
    Route::delete('/org-structure/staff/{staff}', [OrgStructureController::class, 'destroyStaff'])->name('org-structure.staff.destroy');

    // جاءت من قسم الخدمات والتراخيص: إدارة الموظفين شأن إداري لا خدمة.
    Route::get('/staff-management', [ServiceStaffController::class, 'index'])->name('staff-management');
    Route::post('/staff-management', [ServiceStaffController::class, 'store'])->name('staff-management.store');
    Route::put('/staff-management/{staff}', [ServiceStaffController::class, 'update'])->name('staff-management.update');
    Route::post('/staff-management/{staff}/section', [ServiceStaffController::class, 'reassign'])->name('staff-management.reassign');
    Route::delete('/staff-management/{staff}', [ServiceStaffController::class, 'destroy'])->name('staff-management.destroy');

    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');

    Route::get('/admin-tasks', [AdminTaskController::class, 'index'])->name('admin-tasks');
    Route::post('/admin-tasks', [AdminTaskController::class, 'store'])->name('admin-tasks.store');
    Route::put('/admin-tasks/{task}', [AdminTaskController::class, 'update'])->name('admin-tasks.update');
    Route::post('/admin-tasks/{task}/complete', [AdminTaskController::class, 'complete'])->name('admin-tasks.complete');
    Route::delete('/admin-tasks/{task}', [AdminTaskController::class, 'destroy'])->name('admin-tasks.destroy');

    Route::get('/staff-notifications', [StaffNotificationController::class, 'index'])->name('staff-notifications');
    Route::post('/staff-notifications/read-all', [StaffNotificationController::class, 'markAllRead'])->name('staff-notifications.read-all');
    Route::post('/staff-notifications/{notification}/read', [StaffNotificationController::class, 'markRead'])->name('staff-notifications.read');

    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts');
    Route::post('/alerts/generate', [AlertController::class, 'generate'])->name('alerts.generate');
    Route::post('/alerts/{alert}/assign', [AlertController::class, 'assign'])->name('alerts.assign');
    Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
};

/*
 * قسم الخدمات والتراخيص — بوابة قائمة بذاتها تحت البادئة /services.
 *
 * ست لوحات تغطّي دورة الخدمة كاملة: الطلب يصل ويُعالَج ويُعتمد فتُصدر رخصته،
 * ويُتابَع من يخالف شروطها. "رخص المواسم" جاءت من المنصة التشغيلية و"الرقابة
 * والامتثال" من لوحة الحكومة — طرفا الدورة نفسها التي يفتحها الطلب. وإدارة
 * الموظفين انتقلت إلى إدارة النظام.
 */
$servicesSection = function (): void {
    /*
     * رئيسة القسم هي خدمات الصيادين: صفحة سرد اللوحات حُذفت لأن القائمة الجانبية
     * تعرض اللوحات كلها، فكانت وسيطًا يُنقر مرتين للوصول إلى الطلبات.
     */
    Route::get('/', [FisherServiceRequestController::class, 'index'])->name('fisher-services');
    Route::post('/', [FisherServiceRequestController::class, 'store'])->name('fisher-services.store');
    Route::post('/{serviceRequest}/process', [FisherServiceRequestController::class, 'process'])->name('fisher-services.process');
    Route::post('/{serviceRequest}/decide', [FisherServiceRequestController::class, 'decide'])->name('fisher-services.decide');
    Route::get('/{serviceRequest}/license', [FisherServiceRequestController::class, 'license'])->name('fisher-services.license');

    Route::get('/my-workspace', [MyWorkspaceController::class, 'index'])->name('my-workspace');
    Route::post('/my-workspace/notifications/{notification}/read', [MyWorkspaceController::class, 'markRead'])->name('my-workspace.read');

    Route::get('/staff-dashboard', [ServiceStaffDashboardController::class, 'index'])->name('staff-dashboard');

    Route::get('/season-licenses', [SeasonLicenseController::class, 'index'])->name('season-licenses');
    Route::post('/season-licenses', [SeasonLicenseController::class, 'store'])->name('season-licenses.store');
    Route::put('/season-licenses/{seasonLicense}', [SeasonLicenseController::class, 'update'])->name('season-licenses.update');

    Route::get('/compliance', [ComplianceController::class, 'index'])->name('compliance');
    Route::post('/compliance', [ComplianceController::class, 'store'])->name('compliance.store');

    Route::get('/support', [SupportTicketController::class, 'index'])->name('support');
    Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
    Route::post('/support/{ticket}/assign', [SupportTicketController::class, 'assign'])->name('support.assign');
    Route::post('/support/{ticket}/resolve', [SupportTicketController::class, 'resolve'])->name('support.resolve');
};

/*
 * قسم الإحصاء — بوابة قائمة بذاتها تحت البادئة /stats.
 *
 * لوحاته الست عشرة كانت موزّعة بين لوحة الحكومة والمنصة التشغيلية، فجُمعت هنا
 * ليصبح للقسم قائمته الجانبية وحده: من الرصد الميداني والاعتماد، إلى التحليل
 * والتقارير، إلى الأسواق والأمن الغذائي.
 */
$statisticsSection = function (): void {
    /*
     * رئيسة القسم هي موجز الإدارة العليا نفسه: صفحة سرد اللوحات حُذفت لأن القائمة
     * الجانبية تعرض اللوحات كلها، فكانت وسيطًا يُنقر مرتين للوصول إلى الموجز.
     */
    Route::get('/', [ExecutiveBriefingController::class, 'index'])->name('executive-briefing');
    Route::get('/export.csv', [ExecutiveBriefingController::class, 'exportCsv'])->name('executive-briefing.csv');
    Route::get('/export.json', [ExecutiveBriefingController::class, 'exportJson'])->name('executive-briefing.json');

    Route::get('/national-indicators', [NationalIndicatorsController::class, 'index'])->name('national-indicators');
    Route::get('/performance-compare', [PerformanceCompareController::class, 'index'])->name('performance-compare');

    Route::get('/field-statistics', [FieldStatisticsController::class, 'index'])->name('field-statistics');
    Route::post('/field-statistics/{trip}/record', [FieldStatisticsController::class, 'record'])->name('field-statistics.record');

    Route::get('/approved-catch', [ApprovedCatchController::class, 'index'])->name('approved-catch');
    Route::post('/approved-catch/{trip}/approve', [ApprovedCatchController::class, 'approve'])->name('approved-catch.approve');

    Route::get('/statistics-officers', [StatisticsOfficerController::class, 'index'])->name('statistics-officers');
    Route::get('/catch-trace', [CatchTraceController::class, 'index'])->name('catch-trace');

    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/ai-assistant', [AiAssistantController::class, 'index'])->name('ai-assistant');

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports');
    Route::get('/reports/{report}/export.csv', [ReportsController::class, 'export'])->name('reports.export');
    Route::get('/reports/{report}/print', [ReportsController::class, 'print'])->name('reports.print');

    Route::get('/monthly-reports', [MonthlyReportsController::class, 'index'])->name('monthly-reports');
    Route::get('/monthly-reports/export.csv', [MonthlyReportsController::class, 'export'])->name('monthly-reports.export');
    Route::get('/monthly-reports/print', [MonthlyReportsController::class, 'print'])->name('monthly-reports.print');

    Route::get('/annual-bulletin', [AnnualBulletinController::class, 'index'])->name('annual-bulletin');
    Route::get('/annual-bulletin/print', [AnnualBulletinController::class, 'print'])->name('annual-bulletin.print');

    Route::get('/markets', [MarketController::class, 'index'])->name('markets');
    Route::get('/supply-chain', [SupplyChainController::class, 'index'])->name('supply-chain');
    Route::get('/food-security', [FoodSecurityController::class, 'index'])->name('food-security');
};

/*
 * مركز المعلومات التشغيلي — السجلات والعمليات. كان بوابة مفتوحة على /admin
 * وصار قسم المدير العام في لوحة الإدارة أدناه، فلا يُقدَّم إلا لمن دخل بدوره.
 *
 * أسماء المسارات تبقى بلا بادئة (governorates، boats…) لأن البادئة `admin.`
 * محجوزة لبوابة المعلومات أعلاه (admin.index، admin.tab)، ولأن القائمة الجانبية
 * والاختبارات تشير إلى هذه الأسماء لا إلى مساراتها.
 */
$operationsConsole = function (): void {
    Route::get('/governorates', [GovernorateController::class, 'index'])->name('governorates');
    Route::get('/governorates/{governorate:name}', [GovernorateController::class, 'show'])->name('governorates.show');

    Route::get('/regions', [RegionController::class, 'index'])->name('regions');
    Route::post('/regions', [RegionController::class, 'store'])->name('regions.store');
    Route::put('/regions/{region}', [RegionController::class, 'update'])->name('regions.update');
    Route::delete('/regions/{region}', [RegionController::class, 'destroy'])->name('regions.destroy');

    Route::get('/species', [SpeciesController::class, 'index'])->name('species');
    Route::post('/species', [SpeciesController::class, 'store'])->name('species.store');
    Route::put('/species/{species}', [SpeciesController::class, 'update'])->name('species.update');

    Route::get('/fishing-seasons', [FishingSeasonController::class, 'index'])->name('fishing-seasons');
    Route::post('/fishing-seasons', [FishingSeasonController::class, 'store'])->name('fishing-seasons.store');
    Route::put('/fishing-seasons/{fishingSeason}', [FishingSeasonController::class, 'update'])->name('fishing-seasons.update');
    Route::post('/fishing-seasons/{fishingSeason}/status', [FishingSeasonController::class, 'updateStatus'])->name('fishing-seasons.status');

    Route::get('/boats', [BoatController::class, 'index'])->name('boats');
    Route::get('/fishers', [FisherController::class, 'index'])->name('fishers');
    Route::get('/trips', [TripController::class, 'index'])->name('trips');
    Route::get('/boat-timeline', [BoatTimelineController::class, 'index'])->name('boat-timeline');
    Route::get('/ports', [PortController::class, 'index'])->name('ports');
    Route::get('/ports/{port}', [PortController::class, 'show'])->name('ports.show');
    Route::get('/fishing-sites', [FishingSiteController::class, 'index'])->name('fishing-sites');

    Route::get('/discrepancy-review', [DiscrepancyReviewController::class, 'index'])->name('discrepancy-review');
    Route::post('/discrepancy-review/{trip}/resolve', [DiscrepancyReviewController::class, 'resolve'])->name('discrepancy-review.resolve');

    Route::get('/bycatch', [BycatchController::class, 'index'])->name('bycatch');
    Route::post('/bycatch', [BycatchController::class, 'store'])->name('bycatch.store');
};

/*
 * لوحة الإدارة — لوحة تطبيق حوات على /admin، وهي وحدها خلف تسجيل الدخول.
 *
 * الدخول بالجوال أو البريد (PanelLoginController)، ثم رئيسة تتفرّع على الدور،
 * ثم صفحات كل دور تحت وسيط panel:<role>. المدير العام يدير حسابات التطبيق
 * ومعه مركز المعلومات التشغيلي كاملًا؛ بوابات الملاك والكباتن والدلالين
 * والتجار تحت الوسيط نفسه كلٌّ بدوره. الملف الشخصي والإشعارات لكل الأدوار.
 *
 * أسماء المسارات بالبادئة panel. — لا admin. لأنها لبوابة المعلومات — وهي ما
 * يميّز صفحتي الدخول عن بعضهما في bootstrap/app.php.
 */
$adminPanel = function () use ($operationsConsole): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [PanelLoginController::class, 'create'])->name('panel.login');
        Route::post('/login', [PanelLoginController::class, 'store'])->middleware('throttle:6,1')->name('panel.login.store');
    });

    Route::post('/logout', [PanelLoginController::class, 'destroy'])->middleware('auth')->name('panel.logout');

    Route::middleware(['auth', 'panel'])->group(function () use ($operationsConsole): void {
        Route::get('/', [PanelHomeController::class, 'index'])->name('panel.home');

        // الملف الشخصي والإشعارات — شاشتا التطبيق المشتركتان بين الأدوار كلها.
        Route::get('/profile', [PanelProfileController::class, 'show'])->name('panel.profile');
        Route::put('/profile', [PanelProfileController::class, 'update'])->name('panel.profile.update');
        Route::post('/profile/avatar', [PanelProfileController::class, 'avatar'])->name('panel.profile.avatar');
        Route::delete('/profile/avatar', [PanelProfileController::class, 'removeAvatar'])->name('panel.profile.avatar.remove');
        Route::post('/profile/password', [PanelProfileController::class, 'password'])->name('panel.profile.password');

        Route::get('/notifications', [PanelNotificationController::class, 'index'])->name('panel.notifications');
        Route::post('/notifications/read-all', [PanelNotificationController::class, 'readAll'])->name('panel.notifications.read-all');
        Route::post('/notifications/{notification}/read', [PanelNotificationController::class, 'read'])->name('panel.notifications.read');

        Route::middleware('panel:super_admin')->group(function () use ($operationsConsole): void {
            Route::get('/users', [PanelUserController::class, 'index'])->name('panel.users');
            Route::post('/users', [PanelUserController::class, 'store'])->name('panel.users.store');
            Route::put('/users/{user}', [PanelUserController::class, 'update'])->name('panel.users.update');
            Route::post('/users/{user}/toggle', [PanelUserController::class, 'toggle'])->name('panel.users.toggle');
            Route::delete('/users/{user}', [PanelUserController::class, 'destroy'])->name('panel.users.destroy');

            // طلبات التسجيل من صفحة الهبوط (ملاك ودلالون) بانتظار الاعتماد.
            Route::get('/registrations', [PanelRegistrationController::class, 'index'])->name('panel.registrations');
            Route::post('/registrations/{registration}/approve', [PanelRegistrationController::class, 'approve'])->name('panel.registrations.approve');
            Route::post('/registrations/{registration}/reject', [PanelRegistrationController::class, 'reject'])->name('panel.registrations.reject');

            // شركات التشغيل وموانئها وحسابات موظفيها — توظّف العدّادين من بوابتها.
            Route::get('/companies', [PanelCompanyController::class, 'index'])->name('panel.companies');
            Route::post('/companies', [PanelCompanyController::class, 'store'])->name('panel.companies.store');
            Route::get('/companies/{company}', [PanelCompanyController::class, 'show'])->name('panel.companies.show');
            Route::put('/companies/{company}', [PanelCompanyController::class, 'update'])->name('panel.companies.update');
            Route::delete('/companies/{company}', [PanelCompanyController::class, 'destroy'])->name('panel.companies.destroy');
            Route::post('/companies/{company}/ports', [PanelCompanyController::class, 'attachPort'])->name('panel.companies.ports.attach');
            Route::delete('/companies/{company}/ports/{port}', [PanelCompanyController::class, 'detachPort'])->name('panel.companies.ports.detach');
            Route::post('/companies/{company}/staff', [PanelCompanyController::class, 'storeStaff'])->name('panel.companies.staff.store');

            // العدّادون كلهم (وزارة وشركات): إيقاف لا ترفعه الشركة، ونقل.
            Route::get('/counters', [PanelCounterController::class, 'index'])->name('panel.counters');
            Route::post('/counters/{counter}/suspend', [PanelCounterController::class, 'suspend'])->name('panel.counters.suspend');
            Route::post('/counters/{counter}/reactivate', [PanelCounterController::class, 'reactivate'])->name('panel.counters.reactivate');
            Route::post('/counters/{counter}/transfer', [PanelCounterController::class, 'transfer'])->name('panel.counters.transfer');

            $operationsConsole();
        });

        /*
         * بوابة المالك: أسطوله وطاقمه وعملاؤه، ثم رحلاته بدورتها والبيع
         * والإرسال للدلال. كل سجل مقيّد بمالكه — انظر ResolvesOwnerRecords.
         */
        Route::prefix('owner')->name('panel.owner.')->middleware('panel:owner')->group(function (): void {
            // الإعدادات: تبويبات البيانات الأساسية أدناه (config/hawat.php → owner_settings)، تُفتح على أولها.
            Route::get('/settings', fn () => redirect()->route(array_values(config('hawat.owner_settings'))[0]['route']))->name('settings');

            Route::get('/boats', [OwnerBoatController::class, 'index'])->name('boats');
            Route::post('/boats', [OwnerBoatController::class, 'store'])->name('boats.store');
            Route::put('/boats/{boat}', [OwnerBoatController::class, 'update'])->name('boats.update');
            Route::delete('/boats/{boat}', [OwnerBoatController::class, 'destroy'])->name('boats.destroy');

            Route::get('/maintenance', [OwnerMaintenanceController::class, 'index'])->name('maintenance');
            Route::post('/maintenance', [OwnerMaintenanceController::class, 'store'])->name('maintenance.store');
            Route::put('/maintenance/{maintenance}', [OwnerMaintenanceController::class, 'update'])->name('maintenance.update');
            Route::delete('/maintenance/{maintenance}', [OwnerMaintenanceController::class, 'destroy'])->name('maintenance.destroy');

            Route::get('/captains', [OwnerCaptainController::class, 'index'])->name('captains');
            Route::post('/captains', [OwnerCaptainController::class, 'store'])->name('captains.store');
            Route::put('/captains/{captain}', [OwnerCaptainController::class, 'update'])->name('captains.update');
            Route::post('/captains/{captain}/toggle', [OwnerCaptainController::class, 'toggle'])->name('captains.toggle');
            Route::delete('/captains/{captain}', [OwnerCaptainController::class, 'destroy'])->name('captains.destroy');

            Route::get('/crew', [OwnerCrewController::class, 'index'])->name('crew');
            Route::post('/crew', [OwnerCrewController::class, 'store'])->name('crew.store');
            Route::put('/crew/{crew}', [OwnerCrewController::class, 'update'])->name('crew.update');
            Route::delete('/crew/{crew}', [OwnerCrewController::class, 'destroy'])->name('crew.destroy');

            foreach (['employees' => OwnerEmployeeController::class, 'customers' => OwnerCustomerController::class, 'vendors' => OwnerVendorController::class] as $segment => $controller) {
                Route::get("/{$segment}", [$controller, 'index'])->name($segment);
                Route::post("/{$segment}", [$controller, 'store'])->name("{$segment}.store");
                Route::put("/{$segment}/{id}", [$controller, 'update'])->name("{$segment}.update");
                Route::delete("/{$segment}/{id}", [$controller, 'destroy'])->name("{$segment}.destroy");
            }

            Route::get('/trips', [OwnerTripController::class, 'index'])->name('trips');
            Route::post('/trips', [OwnerTripController::class, 'store'])->name('trips.store');
            Route::get('/trips/{trip}', [OwnerTripController::class, 'show'])->name('trips.show');
            Route::put('/trips/{trip}', [OwnerTripController::class, 'update'])->name('trips.update');
            Route::post('/trips/{trip}/start', [OwnerTripController::class, 'start'])->name('trips.start');
            Route::post('/trips/{trip}/cancel', [OwnerTripController::class, 'cancel'])->name('trips.cancel');
            Route::post('/trips/{trip}/catch', [OwnerTripController::class, 'submitCatch'])->name('trips.catch');
            Route::get('/catch-log', [OwnerCatchLogController::class, 'index'])->name('catch-log');

            Route::get('/sales', [OwnerSaleController::class, 'index'])->name('sales');
            Route::get('/sales/create', [OwnerSaleController::class, 'create'])->name('sales.create');
            Route::post('/sales', [OwnerSaleController::class, 'store'])->name('sales.store');
            Route::get('/sales/{sale}', [OwnerSaleController::class, 'show'])->name('sales.show');

            Route::get('/consignments', [OwnerConsignmentController::class, 'index'])->name('consignments');
            Route::get('/consignments/create', [OwnerConsignmentController::class, 'create'])->name('consignments.create');
            Route::post('/consignments', [OwnerConsignmentController::class, 'store'])->name('consignments.store');
            Route::get('/consignments/{consignment}', [OwnerConsignmentController::class, 'show'])->name('consignments.show');

            // الدلالون: طلب التعامل بعمولة وأجور مقترحة، وحساب المالك عند كلٍّ منهم.
            Route::get('/dalals', [OwnerDalalController::class, 'index'])->name('dalals');
            Route::post('/dalals/{dalal}/partnership', [OwnerDalalController::class, 'request'])->name('dalals.partnership');

            // تسوية الدلالين (O5): فواتيرهم بمراجعتها، الحسابات والكشوف والاستلام، مخزونهم، أداؤهم.
            Route::get('/dalal-invoices', [OwnerDalalInvoiceController::class, 'index'])->name('dalal-invoices');
            Route::post('/dalal-invoices/accept-all', [OwnerDalalInvoiceController::class, 'acceptAll'])->name('dalal-invoices.accept-all');
            Route::get('/dalal-invoices/{sale}', [OwnerDalalInvoiceController::class, 'show'])->name('dalal-invoices.show');
            Route::get('/dalal-invoices/{sale}/print', [OwnerDalalInvoiceController::class, 'print'])->name('dalal-invoices.print');
            Route::post('/dalal-invoices/{sale}/accept', [OwnerDalalInvoiceController::class, 'accept'])->name('dalal-invoices.accept');
            Route::post('/dalal-invoices/{sale}/reject', [OwnerDalalInvoiceController::class, 'reject'])->name('dalal-invoices.reject');

            Route::get('/dalal-accounts', [OwnerDalalAccountController::class, 'index'])->name('dalal-accounts');
            Route::get('/dalal-accounts/{dalal}', [OwnerDalalAccountController::class, 'show'])->name('dalal-accounts.show');
            Route::get('/dalal-accounts/{dalal}/print', [OwnerDalalAccountController::class, 'print'])->name('dalal-accounts.print');
            Route::post('/dalal-accounts/{dalal}/receipts', [OwnerDalalAccountController::class, 'receipt'])->name('dalal-accounts.receipts.store');
            Route::delete('/dalal-accounts/{dalal}/receipts/{payout}', [OwnerDalalAccountController::class, 'destroyReceipt'])->name('dalal-accounts.receipts.destroy');

            Route::get('/dalal-stock', [OwnerDalalStockController::class, 'index'])->name('dalal-stock');
            Route::get('/dalal-stock/trips/{trip}', [OwnerDalalStockController::class, 'trip'])->name('dalal-stock.trip');

            Route::get('/dalal-performance', [OwnerDalalPerformanceController::class, 'index'])->name('dalal-performance');

            // المصروفات (O1): سندات بالخصم والضريبة وحالة الدفع، والصيانة المكتملة تُرحَّل إليها.
            Route::get('/expenses', [OwnerExpenseController::class, 'index'])->name('expenses');
            Route::get('/expenses/report', [OwnerExpenseController::class, 'report'])->name('expenses.report');
            Route::post('/expenses', [OwnerExpenseController::class, 'store'])->name('expenses.store');
            Route::put('/expenses/{expense}', [OwnerExpenseController::class, 'update'])->name('expenses.update');
            Route::delete('/expenses/{expense}', [OwnerExpenseController::class, 'destroy'])->name('expenses.destroy');
            Route::post('/expenses/{expense}/payment', [OwnerExpenseController::class, 'payment'])->name('expenses.payment');
            Route::get('/expenses/{expense}/print', [OwnerExpenseController::class, 'print'])->name('expenses.print');

            // أصول الأسطول (O2): المعدات، الأصول والإهلاك، الفحوصات، الوثائق وامتثال الطاقم.
            Route::get('/equipment', [OwnerEquipmentController::class, 'index'])->name('equipment');
            Route::post('/equipment', [OwnerEquipmentController::class, 'store'])->name('equipment.store');
            Route::put('/equipment/{equipment}', [OwnerEquipmentController::class, 'update'])->name('equipment.update');
            Route::delete('/equipment/{equipment}', [OwnerEquipmentController::class, 'destroy'])->name('equipment.destroy');

            Route::get('/assets', [OwnerAssetController::class, 'index'])->name('assets');
            Route::get('/assets/print', [OwnerAssetController::class, 'registerPrint'])->name('assets.print');
            Route::get('/assets/depreciation', [OwnerAssetController::class, 'depreciation'])->name('assets.depreciation');
            Route::get('/assets/depreciation/print', [OwnerAssetController::class, 'depreciationPrint'])->name('assets.depreciation.print');
            Route::post('/assets', [OwnerAssetController::class, 'store'])->name('assets.store');
            Route::put('/assets/{asset}', [OwnerAssetController::class, 'update'])->name('assets.update');
            Route::delete('/assets/{asset}', [OwnerAssetController::class, 'destroy'])->name('assets.destroy');

            Route::get('/inspections', [OwnerInspectionController::class, 'index'])->name('inspections');
            Route::post('/inspections', [OwnerInspectionController::class, 'store'])->name('inspections.store');
            Route::put('/inspections/{inspection}', [OwnerInspectionController::class, 'update'])->name('inspections.update');
            Route::delete('/inspections/{inspection}', [OwnerInspectionController::class, 'destroy'])->name('inspections.destroy');

            Route::get('/documents', [OwnerDocumentController::class, 'index'])->name('documents');
            Route::post('/documents', [OwnerDocumentController::class, 'store'])->name('documents.store');
            Route::put('/documents/{document}', [OwnerDocumentController::class, 'update'])->name('documents.update');
            Route::delete('/documents/{document}', [OwnerDocumentController::class, 'destroy'])->name('documents.destroy');

            // مال الطاقم (O3): إعداد الأجور، السلف، مسيرات الرواتب لكل قارب وشهر.
            Route::get('/crew-pay', [OwnerCrewPayController::class, 'index'])->name('crew-pay');
            Route::put('/crew-pay/{fisher}', [OwnerCrewPayController::class, 'update'])->name('crew-pay.update');
            Route::put('/crew-pay/boats/{boat}', [OwnerCrewPayController::class, 'boatShare'])->name('crew-pay.boat');
            Route::get('/crew-pay/{fisher}/statement', [OwnerCrewPayController::class, 'statement'])->name('crew-pay.statement');

            Route::get('/advances', [OwnerAdvanceController::class, 'index'])->name('advances');
            Route::post('/advances', [OwnerAdvanceController::class, 'store'])->name('advances.store');
            Route::delete('/advances/{advance}', [OwnerAdvanceController::class, 'destroy'])->name('advances.destroy');

            Route::get('/payrolls', [OwnerPayrollController::class, 'index'])->name('payrolls');
            Route::post('/payrolls', [OwnerPayrollController::class, 'store'])->name('payrolls.store');
            Route::get('/payrolls/{payroll}', [OwnerPayrollController::class, 'show'])->name('payrolls.show');
            Route::get('/payrolls/{payroll}/print', [OwnerPayrollController::class, 'print'])->name('payrolls.print');
            Route::delete('/payrolls/{payroll}', [OwnerPayrollController::class, 'destroy'])->name('payrolls.destroy');
            Route::post('/payrolls/{payroll}/pay-all', [OwnerPayrollController::class, 'payAll'])->name('payrolls.pay-all');
            Route::put('/payrolls/{payroll}/lines/{line}', [OwnerPayrollController::class, 'updateLine'])->name('payrolls.lines.update');
            Route::post('/payrolls/{payroll}/lines/{line}/pay', [OwnerPayrollController::class, 'payLine'])->name('payrolls.lines.pay');

            // إغلاق الشهر (O4): معاينة ثم إغلاق يثبّت الأرقام ويقفل الشهر، وإعادة فتح آخر شهر.
            Route::get('/month-closings', [OwnerMonthClosingController::class, 'index'])->name('month-closings');
            Route::get('/month-closings/preview', [OwnerMonthClosingController::class, 'preview'])->name('month-closings.preview');
            Route::post('/month-closings', [OwnerMonthClosingController::class, 'store'])->name('month-closings.store');
            Route::get('/month-closings/{closing}', [OwnerMonthClosingController::class, 'show'])->name('month-closings.show');
            Route::get('/month-closings/{closing}/print', [OwnerMonthClosingController::class, 'print'])->name('month-closings.print');
            Route::delete('/month-closings/{closing}', [OwnerMonthClosingController::class, 'reopen'])->name('month-closings.reopen');

            // التقارير (O6) بنسق hispa: المركز، وكل تقرير صفحة ويب + ورقة A4 للطباعة.
            Route::get('/reports', [OwnerReportController::class, 'index'])->name('reports');
            Route::get('/reports/{report}/{mode?}', [OwnerReportController::class, 'show'])
                ->whereIn('report', array_keys(OwnerReports::REPORTS))
                ->whereIn('mode', ['print'])
                ->name('reports.show');
        });

        /*
         * بوابة العدّاد: طابور ميناء عمله — ما عاد بمصيده يُستلم ثم يُعدّ
         * صنفًا صنفًا، وتقرير الرحلة المفصّل. رحلة ميناء آخر 404 — انظر
         * ResolvesCounterRecords.
         */
        Route::prefix('counter')->name('panel.counter.')->middleware('panel:counter')->group(function (): void {
            Route::get('/trips', [CounterTripController::class, 'index'])->name('trips');
            Route::get('/trips/{trip}', [CounterTripController::class, 'show'])->name('trips.show');
            Route::get('/trips/{trip}/report', [CounterTripController::class, 'report'])->name('trips.report');
            Route::post('/trips/{trip}/receive', [CounterTripController::class, 'receive'])->name('trips.receive');
            Route::post('/trips/{trip}/count', [CounterTripController::class, 'count'])->name('trips.count');
        });

        /*
         * بوابة شركة التشغيل: جولات التوظيف في موانئها، وطلبات العدّادين
         * واعتمادها، وعدّادوها ونشاطهم وإيقافهم ونقلهم. كل سجل مقيّد بشركة
         * الموظف — انظر ResolvesCompanyRecords.
         */
        Route::prefix('company')->name('panel.company.')->middleware('panel:company')->group(function (): void {
            Route::get('/hiring', [CompanyHiringRoundController::class, 'index'])->name('hiring');
            Route::post('/hiring', [CompanyHiringRoundController::class, 'store'])->name('hiring.store');
            Route::put('/hiring/{round}', [CompanyHiringRoundController::class, 'update'])->name('hiring.update');
            Route::post('/hiring/{round}/open', [CompanyHiringRoundController::class, 'open'])->name('hiring.open');
            Route::post('/hiring/{round}/close', [CompanyHiringRoundController::class, 'close'])->name('hiring.close');

            Route::get('/applications', [CompanyApplicationController::class, 'index'])->name('applications');
            Route::post('/applications/{application}/approve', [CompanyApplicationController::class, 'approve'])->name('applications.approve');
            Route::post('/applications/{application}/reject', [CompanyApplicationController::class, 'reject'])->name('applications.reject');

            Route::get('/counters', [CompanyCounterController::class, 'index'])->name('counters');
            Route::post('/counters/{counter}/suspend', [CompanyCounterController::class, 'suspend'])->name('counters.suspend');
            Route::post('/counters/{counter}/reactivate', [CompanyCounterController::class, 'reactivate'])->name('counters.reactivate');
            Route::post('/counters/{counter}/transfer', [CompanyCounterController::class, 'transfer'])->name('counters.transfer');
        });

        /*
         * بوابة الدلال: مخزونه مما أرسله الملاك، والبيع منه بالسطور والفاتورة،
         * وعملاؤه، وطلبات الملاك، والصيّادون المرتبطون ودفعاتهم، والتقارير،
         * والإعدادات. كل سجل مقيّد به — انظر ResolvesDalalRecords.
         */
        Route::prefix('dalal')->name('panel.dalal.')->middleware('panel:dalal')->group(function (): void {
            Route::get('/stock', [DalalStockController::class, 'index'])->name('stock');

            Route::get('/sales', [DalalSaleController::class, 'index'])->name('sales');
            Route::get('/sales/create', [DalalSaleController::class, 'create'])->name('sales.create');
            Route::post('/sales', [DalalSaleController::class, 'store'])->name('sales.store');
            Route::get('/sales/{sale}', [DalalSaleController::class, 'show'])->name('sales.show');
            Route::post('/sales/{sale}/payment', [DalalSaleController::class, 'payment'])->name('sales.payment');
            Route::post('/sales/{sale}/reviews/{review}/reply', [DalalSaleController::class, 'reply'])->name('sales.reviews.reply');

            Route::get('/customers', [DalalCustomerController::class, 'index'])->name('customers');
            Route::post('/customers', [DalalCustomerController::class, 'store'])->name('customers.store');
            Route::put('/customers/{id}', [DalalCustomerController::class, 'update'])->name('customers.update');
            Route::delete('/customers/{id}', [DalalCustomerController::class, 'destroy'])->name('customers.destroy');

            Route::get('/requests', [DalalPartnershipController::class, 'index'])->name('requests');
            Route::post('/requests/{partnership}/accept', [DalalPartnershipController::class, 'accept'])->name('requests.accept');
            Route::post('/requests/{partnership}/reject', [DalalPartnershipController::class, 'reject'])->name('requests.reject');

            Route::get('/owners', [DalalOwnerController::class, 'index'])->name('owners');
            Route::post('/owners/{owner}/payouts', [DalalOwnerController::class, 'payout'])->name('owners.payout');

            Route::get('/reports', [DalalReportController::class, 'index'])->name('reports');
            Route::get('/reports/{type}', [DalalReportController::class, 'show'])->name('reports.show');

            Route::get('/settings', [DalalSettingsController::class, 'index'])->name('settings');
            Route::put('/settings', [DalalSettingsController::class, 'update'])->name('settings.update');
            Route::post('/settings/logo', [DalalSettingsController::class, 'logo'])->name('settings.logo');
            Route::delete('/settings/logo', [DalalSettingsController::class, 'removeLogo'])->name('settings.logo.remove');
            // عمال الدكة أفرادًا (تبويب العمالة في الإعدادات) — التعطيل بدل الحذف.
            Route::post('/settings/workers', [DalalSettingsController::class, 'storeWorker'])->name('settings.workers.store');
            Route::put('/settings/workers/{worker}', [DalalSettingsController::class, 'updateWorker'])->name('settings.workers.update');
            Route::post('/settings/workers/{worker}/toggle', [DalalSettingsController::class, 'toggleWorker'])->name('settings.workers.toggle');
            // أعداد العمالة القديمة: تُحذف بعد تسجيل العمال أفرادًا.
            Route::delete('/settings/worker-counts/{count}', [DalalSettingsController::class, 'destroyWorkerCount'])->name('settings.worker-counts.destroy');
        });
    });
};

/*
 * عنوان مطلق على مضيف بوابات الوزارة بمخطّط الطلب نفسه — للتحويل إليه من مضيف آخر.
 */
$govUrl = fn (Request $request, string $path): string => $request->getScheme().'://'.config('hawat.gov_domain').$path;

/*
 * موضع صفحة من بوابة المعلومات القديمة في إدارة النظام: كانت على مضيفها
 * (info.hawat.sa) ثم تحت /info، تبويباتها تحت admin/ ودخولها على login.
 */
$fromInfoPortal = fn (Request $request, ?string $path = null) => redirect()->away($govUrl($request, match (true) {
    $path === 'login' => '/login',
    str_starts_with((string) $path, 'admin/') => '/subadmin/'.substr($path, strlen('admin/')),
    default => '/subadmin',
}), 301);

/*
 * بوابات الوزارة الثلاث على مضيفها (gov.hawat.sa): صناديقها على "/"، ثم الإحصاء
 * تحت /stats ومعه شاشة العرض تحت /screens، والخدمات والتراخيص تحت /services،
 * وإدارة النظام تحت /subadmin (ونصفها الآخر بوابة المعلومات) بدخولها على /login.
 */
$ministrySite = function () use ($screens, $statisticsSection, $subAdministration, $infoPortal, $servicesSection): void {
    // صفحة اختيار البوابات — كانت على /sections في النطاق الرئيسي ومعها صندوق التطبيق.
    Route::view('/', 'portal')->name('portal');

    Route::prefix('screens')->name('screens.')->group($screens);

    Route::prefix('stats')->name('stats.')->group($statisticsSection);

    /*
     * دخول إدارة النظام، واسمه "login" مجرّدًا لأن وسيط auth يحوّل إليه بهذا
     * الاسم. والمحاولات مخنوقة: ستّ في الدقيقة تكفي من يعرف كلمته.
     */
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [LoginController::class, 'create'])->name('login');
        Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
    });
    Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

    // إدارة النظام بنصفيها خلف دخولها — انظر redirectGuestsTo في bootstrap/app.php.
    Route::prefix('subadmin')->middleware('auth')->group(function () use ($subAdministration, $infoPortal): void {
        Route::name('subadmin.')->group($subAdministration);
        $infoPortal();
    });

    Route::prefix('services')->name('services.')->group($servicesSection);

    /*
     * مواضع اللوحات قبل استقلال أقسام الإحصاء والإدارة الفرعية والخدمات
     * والتراخيص ببواباتها. التحويل دائم حفاظًا على الروابط المحفوظة والمُرسلة،
     * وليست مسارات مكرّرة: لا شيء يُقدَّم منها.
     */
    foreach ([
        '/gov/statistics' => '/stats',
        '/gov/executive-briefing' => '/stats',
        '/stats/executive-briefing' => '/stats',
        '/gov/national-indicators' => '/stats/national-indicators',
        '/gov/performance-compare' => '/stats/performance-compare',
        '/gov/field-statistics' => '/stats/field-statistics',
        '/gov/approved-catch' => '/stats/approved-catch',
        '/gov/ai-assistant' => '/stats/ai-assistant',
        '/gov/reports' => '/stats/reports',
        '/gov/monthly-reports' => '/stats/monthly-reports',
        '/gov/annual-bulletin' => '/stats/annual-bulletin',
        '/gov/food-security' => '/stats/food-security',
        '/gov/alerts' => '/subadmin/alerts',
        '/services/fisher-services' => '/services',
        '/gov/compliance' => '/services/compliance',
        '/services/staff-management' => '/subadmin/staff-management',
    ] as $vacated => $destination) {
        Route::permanentRedirect($vacated, $destination);
    }

    // وما بقي تحت /gov لوحاتُ القاعة نفسها، وقد صارت تحت /screens.
    Route::permanentRedirect('/gov/{screen?}', '/screens/{screen?}');

    /*
     * صفحة المستخدمين في /subadmin كانت نسخة للعرض فقط من تبويب بوابة المعلومات
     * الذي يحرّرهم، فبقي التبويب وحده. وتبويبات سقطت من البوابة لأن لصفحتها
     * موضعًا واحدًا خارجها. التحويل دائم حتى لا تتعطّل الروابط المحفوظة.
     */
    foreach (['/subadmin/users' => 'admin.tab', '/subadmin/licenses' => 'services.season-licenses', '/subadmin/audit' => 'subadmin.audit-log'] as $vacated => $destination) {
        Route::get($vacated, fn () => redirect()->route($destination, $destination === 'admin.tab' ? 'permissions' : [], 301));
    }
};

/*
 * النطاق الرئيسي (hawat.sa): صفحة الهبوط العامة والتسجيل منها، والتقديم لوظيفة
 * عدّاد، والفاتورة الموقّعة، وتطبيق حوات تحت /admin مع توثيق واجهته.
 */
$mainSite = function () use ($adminPanel, $govUrl, $fromInfoPortal): void {
    // الجذر صفحة الهبوط العامة: التعريف بحوات ومسار الصيد والتواصل.
    Route::view('/', 'landing')->name('landing');
    Route::post('/register-request', [RegistrationRequestController::class, 'store'])->middleware('throttle:5,1')->name('landing.register');

    // التقديم لوظيفة عدّاد في جولات شركات التشغيل، والمتابعة بمفتاح الطلب.
    Route::get('/counter/apply', [CounterApplicationController::class, 'create'])->name('counter-apply');
    Route::post('/counter/apply', [CounterApplicationController::class, 'store'])->middleware('throttle:5,1')->name('counter-apply.store');
    Route::get('/counter/apply/{token}', [CounterApplicationController::class, 'show'])->name('counter-apply.show');
    Route::post('/counter/apply/{token}/verify', [CounterApplicationController::class, 'verify'])->middleware('throttle:10,1')->name('counter-apply.verify');
    Route::post('/counter/apply/{token}/resend', [CounterApplicationController::class, 'resend'])->middleware('throttle:3,1')->name('counter-apply.resend');
    Route::post('/counter/apply/{token}/withdraw', [CounterApplicationController::class, 'withdraw'])->name('counter-apply.withdraw');

    // الفاتورة المطبوعة برابط موقّع — يفتحها التطبيق ويشاركها بلا دخول (Sale::invoiceUrl).
    Route::get('/invoices/{sale}', [InvoiceController::class, 'show'])->middleware('signed')->name('invoices.show');

    Route::prefix('admin')->group($adminPanel);

    /*
     * توثيق واجهة التطبيق: مواصفة OpenAPI من docs/api وعارض Swagger UI عليها.
     * صفحتا ويب لا مساري API — فالعارض HTML والمواصفة ملف ثابت.
     */
    Route::view('/api/docs', 'api.docs')->name('api.docs');
    Route::get('/api/openapi.yaml', fn () => response(file_get_contents(base_path('docs/api/openapi.yaml')), 200, ['Content-Type' => 'application/yaml; charset=utf-8']))->name('api.openapi');

    /*
     * بوابات الوزارة كانت على هذا النطاق: تُحوَّل دائمًا إلى مضيفها بالمسار
     * والاستعلام نفسيهما، وصفحة اختيارها /sections إلى جذره.
     *
     * تحويلات /admin/* القديمة أُسقطت: صار /admin لوحة تطبيق حوات ومساراتها
     * (users، settings، markets…) لها، والتحويل الدائم يعلق في المتصفح.
     */
    $sameOnGov = fn (Request $request) => redirect()->away($govUrl($request, $request->getRequestUri()), 301);

    foreach (['stats', 'gov', 'services', 'subadmin'] as $prefix) {
        Route::get("/{$prefix}/{path?}", $sameOnGov)->where('path', '.*');
    }
    Route::get('/login', $sameOnGov);
    Route::get('/sections', fn (Request $request) => redirect()->away($govUrl($request, '/'), 301));
    Route::get('/info/{path?}', $fromInfoPortal)->where('path', '.*');
};

Route::domain(config('hawat.gov_domain'))->group($ministrySite);

/*
 * بيئة الاختبار تُخدم تحت مسار (hawat.sa/staging): المسار المقيّد بمضيف يُبنى
 * عنوانه من المضيف وحده فتسقط البادئة ويشير إلى الإنتاج، فلا يُقيَّد هناك.
 */
if (parse_url((string) config('app.url'), PHP_URL_PATH)) {
    Route::group([], $mainSite);
} else {
    Route::domain(config('hawat.domain'))->group($mainSite);
}

// مضيف بوابة المعلومات القديم: كل صفحة منه إلى موضعها في إدارة النظام.
Route::domain('info.'.config('hawat.domain'))->group(function () use ($fromInfoPortal): void {
    Route::get('/{path?}', $fromInfoPortal)->where('path', '.*');
});

/*
 * مضيف غير هؤلاء يصل التطبيق (www. أو عنوان الخادم) يُحوَّل إلى النطاق الرئيسي
 * بالمسار نفسه. وعلى المضيفَين أنفسهما المسار الذي لا يطابق شيئًا 404.
 */
Route::fallback(function (Request $request) {
    abort_if(in_array($request->getHost(), [config('hawat.domain'), config('hawat.gov_domain')], true), 404);

    return redirect()->away($request->getScheme().'://'.config('hawat.domain').$request->getRequestUri(), 301);
});
