<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Boat;
use App\Models\BoatMaintenance;
use App\Models\Consignment;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\DalalPartnership;
use App\Models\DalalPayout;
use App\Models\DalalProfile;
use App\Models\DocumentType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Fisher;
use App\Models\FisherRole;
use App\Models\FishingSeason;
use App\Models\GearType;
use App\Models\MaintenanceType;
use App\Models\MonthClosing;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Payroll;
use App\Models\PayType;
use App\Models\Role;
use App\Models\StatisticsOfficer;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Dalal\DalalAccounts;
use App\Services\Owner\CrewPayService;
use App\Services\Owner\ExpenseService;
use App\Services\Owner\FleetService;
use App\Services\Owner\MonthClosingService;
use App\Services\Owner\PayrollService;
use App\Services\Sales\SaleService;
use App\Services\Stock\StockLedger;
use App\Services\Trips\TripService;
use Illuminate\Database\Seeder;

/**
 * يجهّز المالك التجريبي (0500000001) ليُختبر مسار الرحلة كاملًا من أول دخول:
 * قاربان من الأسطول المبذور يُنسبان إليه مع رحلاتهما، وكابتن على قاربه الأول
 * (سجلّ صياد بلا دخول — المالك يدير الرحلة عنه)، وعدّاد على ميناء القارب
 * الأول بجواله 0500000003،
 * وزبونان، ودلال بجواله 0500000004 باتفاق مقبول ومخزون مرسَل إليه، وبضعة
 * مصروفات وأصول أسطول. لا يُنشئ قوارب جديدة.
 */
class DemoOwnerSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('phone', '0500000001')->first();

        if ($owner === null) {
            return;
        }

        $password = env('SEED_PASSWORD', 'hawat@2026');

        $boats = Boat::whereNull('owner_id')->orderBy('id')->limit(2)->get();

        foreach ($boats as $boat) {
            $boat->update(['owner_id' => $owner->id]);
            Trip::where('boat_id', $boat->id)->whereNull('owner_id')->update(['owner_id' => $owner->id]);
        }

        // قارب المالك الأول — يُقرأ من أسطوله لا من الأسطول الحرّ حتى يعمل
        // البذّار في التشغيل الثاني أيضًا (البذر الأول أسند القوارب إليه).
        $homeBoat = Boat::forOwner($owner)->orderBy('id')->first();

        if ($homeBoat !== null) {
            $captain = Fisher::updateOrCreate(['national_id' => '1000000002'], [
                'owner_id' => $owner->id,
                'is_captain' => true,
                'port_id' => $homeBoat->port_id,
                'boat_id' => $homeBoat->id,
                'name' => 'كابتن تجريبي',
                'phone' => '0500000002',
                'fisher_role_id' => FisherRole::named(Fisher::CAPTAIN_ROLE)->id,
                'role' => Fisher::CAPTAIN_ROLE,
                'status' => Fisher::ACTIVE,
            ]);
            $homeBoat->update(['captain_id' => $captain->id]);

            Trip::where('boat_id', $homeBoat->id)->whereNull('captain_id')->update(['captain_id' => $captain->id, 'captain_name' => $captain->name]);

            // رحلة بانتظار الانطلاق دائمًا — ليُجرَّب البدء والإلغاء وإرسال المخرجات.
            if (! Trip::forOwner($owner)->scheduled()->exists()) {
                app(TripService::class)->create($owner, ['boat_id' => $homeBoat->id, 'captain_id' => $captain->id, 'planned_days' => 3]);
            }
        }

        // الرحلات المبذورة التي عُدّت قبل أن يكون لها مالك: يُفتح مصيدها للبيع
        // الآن حتى تظهر في "الرحلات التي تحتاج تسجيل البيع" من أول دخول.
        Trip::forOwner($owner)
            ->whereIn('status', [Trip::AWAITING_APPROVAL, Trip::APPROVED])
            ->where('sale_status', Trip::SALE_NOT_STARTED)
            ->whereHas('catchRecords')
            ->get()
            ->each(fn (Trip $trip) => app(TripService::class)->openForSale($trip));

        if ($homeBoat !== null) {
            // العدّاد موظف الإحصاء في ميناء قارب المالك: يصله طابور ما يعود إليه.
            $counter = User::firstOrCreate(
                ['phone' => '0500000003'],
                [
                    'name' => 'عدّاد تجريبي',
                    'password' => $password,
                    'role_id' => Role::key(Role::COUNTER)->id,
                ],
            );

            StatisticsOfficer::updateOrCreate(['user_id' => $counter->id], [
                'port_id' => $homeBoat->port_id,
                'name' => $counter->name,
                'phone' => $counter->phone,
                'employee_number' => StatisticsOfficer::numberFor($counter),
                'shift' => 'صباحية',
                'status' => 'نشط',
            ]);
        }

        foreach (['سوق السمك المركزي' => '0555000001', 'مطاعم الخليج' => '0555000002'] as $name => $phone) {
            Customer::firstOrCreate(
                ['account_user_id' => $owner->id, 'name' => $name],
                ['phone' => $phone, 'customer_type_id' => CustomerType::named('منتظم')->id],
            );
        }

        $this->seedDalal($owner, $homeBoat, $password);
        $this->seedDalalSettlement($owner);
        $this->seedExpenses($owner, $homeBoat);
        $this->seedFleetAssets($owner, $homeBoat);
        $this->seedCrewMoney($owner, $homeBoat);
        $this->seedMonthClosing($owner, $homeBoat);
    }

    /**
     * تسوية الدلال التجريبية (O5): الدلال التجريبي يدفع للمالك 60% من مستحقه
     * بحوالة، فتظهر فاتورته "مسدَّدة جزئيًا" والرصيد الباقي في حساباته، وتبقى
     * قيد مراجعة المالك ليجرّب القبول والرفض. مرة واحدة.
     */
    private function seedDalalSettlement(User $owner): void
    {
        $dalal = User::where('phone', '0500000004')->first();

        if ($dalal === null || DalalPayout::forDalal($dalal)->where('owner_id', $owner->id)->exists()) {
            return;
        }

        $accounts = app(DalalAccounts::class);
        $due = $accounts->dueTo($dalal, $owner);

        if ($due < 1) {
            return;
        }

        $accounts->recordPayout($dalal, $owner, [
            'amount' => round($due * 0.6, 2),
            'payment_method_id' => PaymentMethod::named('تحويل بنكي')->id,
            'notes' => 'دفعة تجريبية من مبيعات الدكة',
        ]);
    }

    /**
     * إغلاق الشهر التجريبي (O4): يُغلق الشهر الماضي فيُنشأ مسيره ويُقفل، وما
     * أجّله من إهلاك الأصول يدخل مسير الشهر الجاري. مرة واحدة.
     */
    private function seedMonthClosing(User $owner, ?Boat $homeBoat): void
    {
        if ($homeBoat === null || MonthClosing::forOwner($owner)->exists()) {
            return;
        }

        $previous = now()->startOfMonth()->subMonth();
        app(MonthClosingService::class)->close($owner, $previous->year, $previous->month, 'إغلاق تجريبي');
    }

    /**
     * مال الطاقم التجريبي (O3): الكابتن بنسبة خاصة، بحّاران بالأسهم، وطبّاخ
     * براتب ثابت؛ سلفة لبحّار، ومسير الشهر الجاري بسطر مسدَّد. مرة واحدة.
     */
    private function seedCrewMoney(User $owner, ?Boat $homeBoat): void
    {
        if ($homeBoat === null || Payroll::forOwner($owner)->exists()) {
            return;
        }

        $share = PayType::named(PayType::SHARE)->id;
        $fixed = PayType::named(PayType::FIXED)->id;

        Fisher::forOwner($owner)->captains()->where('boat_id', $homeBoat->id)
            ->update(['pay_type_id' => $share, 'custom_share_percent' => 20]);

        $crew = [];
        foreach ([['بحّار تجريبي أول', '1000000011', 'بحّار', $share, null, 1], ['بحّار تجريبي ثانٍ', '1000000012', 'بحّار', $share, null, 1], ['طبّاخ القارب', '1000000013', 'طبّاخ', $fixed, 1800, 1]] as [$name, $nationalId, $role, $type, $salary, $shares]) {
            $crew[] = Fisher::updateOrCreate(['national_id' => $nationalId], [
                'owner_id' => $owner->id,
                'boat_id' => $homeBoat->id,
                'port_id' => $homeBoat->port_id,
                'name' => $name,
                'fisher_role_id' => FisherRole::named($role)->id,
                'status' => 'نشط',
                'pay_type_id' => $type,
                'fixed_salary' => $salary,
                'profit_shares' => $shares,
            ]);
        }

        app(CrewPayService::class)->recordAdvance($owner, $crew[0], [
            'date' => now()->startOfMonth()->addDays(min(2, now()->day - 1))->toDateString(),
            'amount' => 400,
            'payment_method_id' => PaymentMethod::query()->ordered()->value('id'),
            'notes' => 'سلفة قبل الرحلة',
        ]);

        $payrolls = app(PayrollService::class);
        $payroll = $payrolls->generate($owner, $homeBoat, now()->year, now()->month);
        $cook = $payroll->lines()->where('fisher_id', $crew[2]->id)->first();

        if ($cook) {
            $payrolls->payLine($cook, PaymentMethod::query()->ordered()->value('id'), $owner);
        }
    }

    /**
     * أصول الأسطول التجريبية (O2): معدات بموسم، أصلان يُهلكان، فحص قديم يجعل
     * موعد القارب قريبًا، ووثيقة منتهية وأخرى تنتهي قريبًا — فتظهر تنبيهات
     * الرئيسة من أول دخول. مرة واحدة فقط.
     */
    private function seedFleetAssets(User $owner, ?Boat $homeBoat): void
    {
        if ($homeBoat === null || Asset::forOwner($owner)->exists()) {
            return;
        }

        $fleet = app(FleetService::class);

        $fleet->saveEquipment($owner, [
            'name' => 'شبكة خيشومية 40 م',
            'boat_id' => $homeBoat->id,
            'gear_type_id' => GearType::query()->value('id'),
            'quantity' => 4,
            'unit_cost' => 250,
            'purchase_date' => now()->subMonths(2)->toDateString(),
            'season_ids' => FishingSeason::query()->limit(1)->pluck('id')->all(),
        ]);

        foreach ([['محرك رئيسي 200 حصان', 'محرك', 60000, 5000, 8, 20], ['جهاز ملاحة ورادار', 'أجهزة ملاحة واتصال', 9000, 0, 3, 7]] as [$name, $type, $cost, $salvage, $life, $monthsAgo]) {
            $fleet->saveAsset($owner, [
                'asset_type_id' => AssetType::named($type)->id,
                'boat_id' => $homeBoat->id,
                'name' => $name,
                'purchase_date' => now()->subMonths($monthsAgo)->startOfMonth()->toDateString(),
                'purchase_cost' => $cost,
                'salvage_value' => $salvage,
                'useful_life_years' => $life,
                'status' => Asset::ACTIVE,
            ]);
        }

        $fleet->saveInspection([
            'boat_id' => $homeBoat->id,
            'inspection_date' => now()->subYear()->addDays(20)->toDateString(),
            'inspector' => 'حرس الحدود — ميناء القطيف',
            'result' => 'مطابق',
        ]);

        $captainFisher = Fisher::forOwner($owner)->captains()->first();

        $fleet->saveDocument($owner, [
            'holder_type' => 'boat', 'holder_id' => $homeBoat->id,
            'document_type_id' => DocumentType::named('تأمين القارب')->id,
            'number' => 'INS-2025-7781', 'issue_date' => now()->subYear()->toDateString(), 'expiry_date' => now()->addDays(18)->toDateString(),
        ]);

        if ($captainFisher !== null) {
            $fleet->saveDocument($owner, [
                'holder_type' => 'crew', 'holder_id' => $captainFisher->id,
                'document_type_id' => DocumentType::named('رخصة بحار')->id,
                'number' => 'SM-55120', 'issue_date' => now()->subYears(2)->toDateString(), 'expiry_date' => now()->subDays(4)->toDateString(),
            ]);
        }
    }

    /**
     * مصروفات تجريبية (O1): وقود وثلج ومؤونة لآخر رحلة للقارب الأول، ورسوم
     * حكومية، وصيانة مكتملة تُرحَّل تلقائيًا. مرة واحدة فقط.
     */
    private function seedExpenses(User $owner, ?Boat $homeBoat): void
    {
        if ($homeBoat === null || Expense::forOwner($owner)->exists()) {
            return;
        }

        $vendor = Vendor::firstOrCreate(['owner_id' => $owner->id, 'name' => 'محطة وقود الميناء'], ['phone' => '0555000010', 'status' => 'نشط']);
        $trip = Trip::forOwner($owner)->where('boat_id', $homeBoat->id)->orderByDesc('id')->first();
        $expenses = app(ExpenseService::class);
        $paid = PaymentStatus::named(ExpenseService::PAID)->id;
        $cash = PaymentMethod::query()->ordered()->value('id');

        $rows = [
            ['وقود', 1800, 15, $paid, $trip?->id, $vendor->id, 'تعبئة وقود قبل الرحلة'],
            ['ثلج', 350, 15, $paid, $trip?->id, null, 'ثلج لحفظ المصيد'],
            ['إعاشة (أكل)', 600, 0, null, $trip?->id, null, 'مؤونة الطاقم'],
            ['رسوم تجديد', 1200, 0, null, null, null, 'تجديد رخصة القارب'],
        ];

        foreach ($rows as [$category, $amount, $vat, $status, $tripId, $vendorId, $description]) {
            $expenses->create($owner, [
                'expense_category_id' => ExpenseCategory::named($category)->id,
                'date' => now()->subDays(random_int(1, 20))->toDateString(),
                'description' => $description,
                'subtotal' => $amount,
                'vat_rate' => $vat,
                'boat_id' => $homeBoat->id,
                'trip_id' => $tripId,
                'vendor_id' => $vendorId,
                'payment_method_id' => $status ? $cash : null,
                'payment_status_id' => $status,
            ]);
        }

        $maintenance = BoatMaintenance::create([
            'boat_id' => $homeBoat->id,
            'maintenance_type_id' => MaintenanceType::query()->ordered()->value('id'),
            'date' => now()->subDays(5)->toDateString(),
            'technician' => 'ورشة الميناء',
            'estimated_cost' => 2500,
            'actual_cost' => 2300,
            'status' => BoatMaintenance::COMPLETED,
        ]);
        $expenses->syncSource($maintenance, $owner);
    }

    /**
     * الدلال التجريبي (0500000004): ملفه ودكته، واتفاق مقبول مع المالك (5% عمولة
     * و2% أجور)، وعميلان، ونصف أول صنف في أول رحلة مفتوحة للبيع مرسَلًا إليه —
     * فيجد مخزونًا يبيع منه من أول دخول. الإرسال مرة واحدة فقط.
     */
    private function seedDalal(User $owner, ?Boat $homeBoat, string $password): void
    {
        $dalal = User::firstOrCreate(
            ['phone' => '0500000004'],
            ['name' => 'دلال تجريبي', 'password' => $password, 'role_id' => Role::key(Role::DALAL)->id],
        );

        DalalProfile::updateOrCreate(['user_id' => $dalal->id], [
            'port_id' => $homeBoat?->port_id,
            'dakka_name' => 'دكة السوق المركزي',
            'dakka_number' => 'D-104',
            'company_name' => 'مؤسسة الدلال التجريبي للأسماك',
            'cr_number' => '1010000004',
            'vat_number' => '300000000000004',
        ]);

        DalalPartnership::updateOrCreate(['owner_id' => $owner->id, 'dalal_id' => $dalal->id], [
            'commission_pct' => 5,
            'wage_pct' => 2,
            'message' => 'نرسل لك مصيد قواربنا للبيع في الدكة.',
            'status' => DalalPartnership::ACCEPTED,
            'responded_at' => now(),
        ]);

        foreach (['مطعم البحر الأحمر' => '0555000011', 'أسماك الواحة' => '0555000012'] as $name => $phone) {
            Customer::firstOrCreate(
                ['account_user_id' => $dalal->id, 'name' => $name],
                ['phone' => $phone, 'customer_type_id' => CustomerType::named('منتظم')->id],
            );
        }

        if (Consignment::forDalal($dalal)->exists()) {
            return;
        }

        $ledger = app(StockLedger::class);
        $trip = Trip::forOwner($owner)->where('sale_status', Trip::SALE_OPEN)->orderBy('id')->first();
        $line = $trip ? collect($ledger->availableLines($owner, $trip))->firstWhere(fn ($l) => $l['available_kg'] > 1) : null;

        if ($line === null) {
            return;
        }

        $sent = round($line['available_kg'] / 2, 2);

        app(SaleService::class)->consign($owner, [
            'trip_id' => $trip->id,
            'dalal_id' => $dalal->id,
            'items' => [['species_id' => $line['species_id'], 'weight_kg' => $sent]],
        ]);

        // بيعة أولى بدفعة جزئية — لتمتلئ رئيسة الدلال (المبيعات والأرباح وغير
        // المحصّل ومستحق الملاك) من أول دخول.
        app(SaleService::class)->sellFromStock($dalal, [
            'customer_id' => Customer::forAccount($dalal)->orderBy('id')->value('id'),
            'paid_amount' => 500,
            'items' => [['species_id' => $line['species_id'], 'weight_kg' => min(42, round($sent / 2, 2)), 'price_per_kg' => 36]],
        ]);
    }
}
