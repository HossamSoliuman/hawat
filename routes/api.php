<?php

use App\Http\Controllers\Api\V1\AppConfigController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\MeController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Counter\DashboardController as CounterDashboardController;
use App\Http\Controllers\Api\V1\CounterApplicationController;
use App\Http\Controllers\Api\V1\Counter\TripController as CounterTripController;
use App\Http\Controllers\Api\V1\Dalal\CustomerController as DalalCustomerController;
use App\Http\Controllers\Api\V1\Dalal\DashboardController as DalalDashboardController;
use App\Http\Controllers\Api\V1\Dalal\OwnerController as DalalOwnerController;
use App\Http\Controllers\Api\V1\Dalal\PartnershipController as DalalPartnershipController;
use App\Http\Controllers\Api\V1\Dalal\ReportController as DalalReportController;
use App\Http\Controllers\Api\V1\Dalal\SaleController as DalalSaleController;
use App\Http\Controllers\Api\V1\Dalal\SettingsController as DalalSettingsController;
use App\Http\Controllers\Api\V1\Dalal\StockController as DalalStockController;
use App\Http\Controllers\Api\V1\Dalal\WorkerController as DalalWorkerController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\Owner\BoatController as OwnerBoatController;
use App\Http\Controllers\Api\V1\Owner\CaptainController as OwnerCaptainController;
use App\Http\Controllers\Api\V1\Owner\CatchLogController as OwnerCatchLogController;
use App\Http\Controllers\Api\V1\Owner\ConsignmentController as OwnerConsignmentController;
use App\Http\Controllers\Api\V1\Owner\CrewController as OwnerCrewController;
use App\Http\Controllers\Api\V1\Owner\CustomerController as OwnerCustomerController;
use App\Http\Controllers\Api\V1\Owner\DalalController as OwnerDalalController;
use App\Http\Controllers\Api\V1\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Api\V1\Owner\EmployeeController as OwnerEmployeeController;
use App\Http\Controllers\Api\V1\Owner\MaintenanceController as OwnerMaintenanceController;
use App\Http\Controllers\Api\V1\Owner\SaleController as OwnerSaleController;
use App\Http\Controllers\Api\V1\Owner\StockController as OwnerStockController;
use App\Http\Controllers\Api\V1\Owner\TripController as OwnerTripController;
use App\Http\Controllers\Api\V1\Owner\VendorController as OwnerVendorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| واجهة تطبيق حوات — /api/v1
|--------------------------------------------------------------------------
|
| رموز Sanctum في ترويسة Authorization: Bearer، والردّ JSON دائمًا (ForceJsonResponse).
| المسارات مجمّعة بالدور كما شاشات التطبيق: auth للجميع، ثم owner و counter
| و dalal كلٌّ تحت وسيط دوره — تُضاف مع بناء بوابة كل دور. الكابتن لا حساب
| له: سجلّ عند المالك، والمالك يدير الرحلة عنه من مسارات owner.
|
| التوثيق: docs/api/openapi.yaml ويُعرض على /api/docs.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    // إعداد التطبيق قبل الدخول: التحديث الإجباري، وأرقام الدعم، والرسائل النصية.
    Route::get('app/config', [AppConfigController::class, 'show'])->middleware('throttle:60,1')->name('app.config');

    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:6,1')->name('login');

        // استعادة كلمة المرور بالجوال: رمز، فتحقق، فكلمة جديدة.
        Route::post('forgot-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:otp')->name('forgot-password');
        Route::post('verify-otp', [PasswordResetController::class, 'verifyCode'])->middleware('throttle:10,1')->name('verify-otp');
        Route::post('reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1')->name('reset-password');

        Route::middleware(['auth:sanctum', 'api.active'])->group(function (): void {
            Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
            Route::get('me', [MeController::class, 'show'])->name('me');
            Route::put('me', [MeController::class, 'update'])->name('me.update');
            Route::post('change-password', [MeController::class, 'changePassword'])->name('change-password');
            Route::post('avatar', [MeController::class, 'updateAvatar'])->name('avatar');
            Route::delete('avatar', [MeController::class, 'removeAvatar'])->name('avatar.remove');
            Route::post('fcm-token', [MeController::class, 'updateFcmToken'])->name('fcm-token');
        });
    });

    /*
     * التقديم لوظيفة عدّاد في جولات شركات التشغيل — بلا حساب؛ الطلب يُتابَع
     * بمفتاحه (token) بعد التقديم.
     */
    Route::get('hiring-rounds', [CounterApplicationController::class, 'rounds'])->name('hiring-rounds');
    Route::prefix('counter-applications')->name('counter-applications.')->group(function (): void {
        Route::post('/', [CounterApplicationController::class, 'store'])->middleware('throttle:5,1')->name('store');
        Route::get('{token}', [CounterApplicationController::class, 'show'])->middleware('throttle:30,1')->name('show');
        Route::post('{token}/verify', [CounterApplicationController::class, 'verify'])->middleware('throttle:10,1')->name('verify');
        Route::post('{token}/resend', [CounterApplicationController::class, 'resend'])->middleware('throttle:3,1')->name('resend');
        Route::post('{token}/withdraw', [CounterApplicationController::class, 'withdraw'])->middleware('throttle:10,1')->name('withdraw');
    });

    /*
     * طلبات POST هنا تقبل ترويسة Idempotency-Key: تكرار الطلب بالمفتاح نفسه
     * بعد ضياع ردّه يعيد الردّ الأول ولا يُنشئ سجلًّا ثانيًا (Idempotent).
     */
    Route::middleware(['auth:sanctum', 'api.active', 'idempotent'])->group(function (): void {
        // القوائم المرجعية لكل الأدوار في ردّ واحد.
        Route::get('lookups', [LookupController::class, 'index'])->name('lookups');

        // إشعارات الحساب الداخل — لكل الأدوار.
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

        /*
         * بوابة المالك: الأسطول والطاقم والعملاء، ثم الرحلات بدورتها
         * والبيع والإرسال للدلال. كل سجل مقيّد بمالكه (404 لغيره).
         */
        Route::prefix('owner')->name('owner.')->middleware('api.role:owner')->group(function (): void {
            Route::get('dashboard', [OwnerDashboardController::class, 'index'])->name('dashboard');

            Route::apiResource('boats', OwnerBoatController::class);
            Route::apiResource('maintenance', OwnerMaintenanceController::class)->except('show');
            // الكابتن سجلّ صياد بلا حساب دخول — يُعطَّل بـ status ويُحذف.
            Route::apiResource('captains', OwnerCaptainController::class);
            Route::post('captains/{captain}/avatar', [OwnerCaptainController::class, 'updateAvatar'])->name('captains.avatar');
            Route::delete('captains/{captain}/avatar', [OwnerCaptainController::class, 'removeAvatar'])->name('captains.avatar.remove');
            Route::apiResource('crew', OwnerCrewController::class);
            Route::post('crew/{crew}/avatar', [OwnerCrewController::class, 'updateAvatar'])->name('crew.avatar');
            Route::delete('crew/{crew}/avatar', [OwnerCrewController::class, 'removeAvatar'])->name('crew.avatar.remove');
            Route::apiResource('employees', OwnerEmployeeController::class)->parameters(['employees' => 'id']);
            Route::apiResource('customers', OwnerCustomerController::class)->parameters(['customers' => 'id']);
            Route::apiResource('vendors', OwnerVendorController::class)->parameters(['vendors' => 'id']);

            Route::apiResource('trips', OwnerTripController::class)->except('destroy');
            Route::post('trips/{trip}/start', [OwnerTripController::class, 'start'])->name('trips.start');
            Route::post('trips/{trip}/cancel', [OwnerTripController::class, 'cancel'])->name('trips.cancel');
            Route::post('trips/{trip}/catch', [OwnerTripController::class, 'submitCatch'])->name('trips.catch');
            Route::get('catch-log', [OwnerCatchLogController::class, 'index'])->name('catch-log');
            Route::get('catch-log/summary', [OwnerCatchLogController::class, 'summary'])->name('catch-log.summary');

            Route::apiResource('sales', OwnerSaleController::class)->only(['index', 'store', 'show']);
            Route::post('sales/{sale}/payments', [OwnerSaleController::class, 'payment'])->name('sales.payments');
            Route::apiResource('consignments', OwnerConsignmentController::class)->only(['index', 'store', 'show']);

            Route::get('dalals', [OwnerDalalController::class, 'index'])->name('dalals');
            Route::post('dalals/{dalal}/partnership', [OwnerDalalController::class, 'partnership'])->name('dalals.partnership');
            Route::get('stock', [OwnerStockController::class, 'index'])->name('stock');
            Route::get('stock/movements', [OwnerStockController::class, 'movements'])->name('stock.movements');
        });

        /*
         * بوابة العدّاد: طابور ميناء عمله — الاستلام ثم العد بالصنف،
         * والتقرير المفصّل. رحلة ميناء آخر 404.
         */
        Route::prefix('counter')->name('counter.')->middleware('api.role:counter')->group(function (): void {
            Route::get('dashboard', [CounterDashboardController::class, 'index'])->name('dashboard');

            Route::get('trips', [CounterTripController::class, 'index'])->name('trips.index');
            Route::get('trips/{trip}', [CounterTripController::class, 'show'])->name('trips.show');
            Route::get('trips/{trip}/report', [CounterTripController::class, 'report'])->name('trips.report');
            Route::post('trips/{trip}/receive', [CounterTripController::class, 'receive'])->name('trips.receive');
            Route::post('trips/{trip}/count', [CounterTripController::class, 'count'])->name('trips.count');
        });

        /*
         * بوابة الدلال: المخزون مما أرسله الملاك، والبيع منه والفاتورة،
         * والعملاء، وطلبات الملاك، والصيّادون المرتبطون ودفعاتهم، والتقارير،
         * والإعدادات. سجلات دلال آخر 404.
         */
        Route::prefix('dalal')->name('dalal.')->middleware('api.role:dalal')->group(function (): void {
            Route::get('dashboard', [DalalDashboardController::class, 'index'])->name('dashboard');
            Route::get('stock', [DalalStockController::class, 'index'])->name('stock');

            Route::apiResource('sales', DalalSaleController::class)->only(['index', 'store', 'show']);
            Route::post('sales/{sale}/payments', [DalalSaleController::class, 'payment'])->name('sales.payments');

            Route::apiResource('customers', DalalCustomerController::class)->parameters(['customers' => 'id']);

            Route::get('partnerships', [DalalPartnershipController::class, 'index'])->name('partnerships.index');
            Route::post('partnerships/{partnership}/accept', [DalalPartnershipController::class, 'accept'])->name('partnerships.accept');
            Route::post('partnerships/{partnership}/reject', [DalalPartnershipController::class, 'reject'])->name('partnerships.reject');

            Route::get('owners', [DalalOwnerController::class, 'index'])->name('owners');
            Route::post('owners/{owner}/payouts', [DalalOwnerController::class, 'payout'])->name('owners.payouts');

            Route::get('reports/{type}', [DalalReportController::class, 'show'])->name('reports.show');

            Route::get('settings', [DalalSettingsController::class, 'show'])->name('settings');
            Route::put('settings', [DalalSettingsController::class, 'update'])->name('settings.update');
            Route::post('settings/logo', [DalalSettingsController::class, 'logo'])->name('settings.logo');
            Route::delete('settings/logo', [DalalSettingsController::class, 'removeLogo'])->name('settings.logo.remove');
            // مهجور: أعداد العمالة القديمة — بعد تسجيل العمال أفرادًا تُحسب الأعداد منهم.
            Route::put('settings/workers', [DalalSettingsController::class, 'workers'])->name('settings.workers');

            // عمال الدكة أفرادًا — التعطيل بالحالة في PUT، لا حذف.
            Route::apiResource('workers', DalalWorkerController::class)->except('destroy');
            Route::post('workers/{worker}/avatar', [DalalWorkerController::class, 'updateAvatar'])->name('workers.avatar');
            Route::delete('workers/{worker}/avatar', [DalalWorkerController::class, 'removeAvatar'])->name('workers.avatar.remove');
        });
    });
});
