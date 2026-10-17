<?php

namespace App\Console\Commands;

use App\Models\Boat;
use App\Models\DalalProfile;
use App\Models\Port;
use App\Models\Role;
use App\Models\User;
use App\Services\Owner\StaffService;
use Database\Seeders\GeographicSeeder;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * يعيد بيئة الاختبار (hawat.sa/staging) إلى حالة معروفة لفريق التطبيق:
 * قاعدة فارغة بالمخطط الحالي، ثم القوائم المرجعية كما في الإنتاج (لقطة بلا
 * حسابات — config('hawat.staging.reference'))، ثم الحسابات التجريبية أدناه
 * بكلمة مرور واحدة من .env (STAGING_ACCOUNTS_PASSWORD).
 *
 * لا يعمل إلا في APP_ENV=staging وعلى قاعدة في اسمها "staging"، فلا يُمسح
 * الإنتاج خطأً.
 */
class StagingReset extends Command
{
    protected $signature = 'hawat:staging-reset';

    protected $description = 'إعادة ضبط بيئة الاختبار: قاعدة جديدة بالقوائم المرجعية والحسابات التجريبية';

    /** الحسابات التجريبية — الكابتنان للمالك الأول. */
    public const ACCOUNTS = [
        ['role' => Role::OWNER, 'name' => 'مالك الاختبار الأول', 'phone' => '0500000101'],
        ['role' => Role::OWNER, 'name' => 'مالك الاختبار الثاني', 'phone' => '0500000102'],
        ['role' => Role::DALAL, 'name' => 'دلال الاختبار', 'phone' => '0500000103'],
        ['role' => Role::CAPTAIN, 'name' => 'كابتن الاختبار الأول', 'phone' => '0500000111', 'national_id' => '1000000111'],
        ['role' => Role::CAPTAIN, 'name' => 'كابتن الاختبار الثاني', 'phone' => '0500000112', 'national_id' => '1000000112'],
    ];

    public function handle(StaffService $staff): int
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');
        $password = (string) config('hawat.staging.password');

        if (! app()->environment('staging') || ! str_contains($database, 'staging')) {
            $this->error('هذا الأمر لبيئة الاختبار وحدها: APP_ENV=staging وقاعدة في اسمها staging.');

            return self::FAILURE;
        }

        if (strlen($password) < 8) {
            $this->error('اضبط STAGING_ACCOUNTS_PASSWORD (8 أحرف على الأقل) في .env أولًا.');

            return self::FAILURE;
        }

        $this->call('migrate:fresh', ['--force' => true]);
        $this->loadReference();
        $this->call('db:seed', ['--class' => RoleSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => LookupSeeder::class, '--force' => true]);

        $port = Port::orderBy('id')->firstOrFail();
        $users = [];

        foreach (self::ACCOUNTS as $account) {
            if ($account['role'] === Role::CAPTAIN) {
                $users[$account['phone']] = $staff->createCaptain($users['0500000101'], [
                    'name' => $account['name'],
                    'phone' => $account['phone'],
                    'password' => $password,
                    'national_id' => $account['national_id'],
                    'port_id' => $port->id,
                ]);

                continue;
            }

            $user = User::create([
                'name' => $account['name'],
                'phone' => $account['phone'],
                'password' => $password,
                'role_id' => Role::key($account['role'])->id,
                'active' => true,
            ]);
            $user->forceFill(['activated_at' => now()])->save();
            $users[$account['phone']] = $user;
        }

        DalalProfile::forUser($users['0500000103'])->update(['port_id' => $port->id, 'dakka_name' => 'دكة الاختبار']);

        // قارب للمالك الأول بكابتنه الأول، والثاني بلا قارب — ليُجرَّب الإعداد من الصفر.
        $boat = Boat::create(['name' => 'قارب الاختبار', 'boat_number' => 'STG-001', 'port_id' => $port->id, 'owner_id' => $users['0500000101']->id, 'status' => 'نشط']);
        $staff->updateCaptain($users['0500000111']->fresh(), [
            'name' => 'كابتن الاختبار الأول',
            'phone' => '0500000111',
            'national_id' => '1000000111',
            'boat_id' => $boat->id,
        ]);

        $this->table(['الدور', 'الاسم', 'الجوال'], collect(self::ACCOUNTS)->map(fn ($a) => [$a['role'], $a['name'], $a['phone']])->all());
        $this->info('كلمة مرور الحسابات كلها: STAGING_ACCOUNTS_PASSWORD في .env.');

        return self::SUCCESS;
    }

    /**
     * القوائم المرجعية: لقطة الإنتاج إن وُجدت، وإلا الجغرافيا التجريبية.
     */
    private function loadReference(): void
    {
        $dump = (string) config('hawat.staging.reference');

        if (is_file($dump)) {
            DB::unprepared(file_get_contents($dump));
            $this->info('حُمّلت القوائم المرجعية من '.$dump);

            return;
        }

        $this->warn('لا لقطة مرجعية في '.$dump.' — تُبذر الجغرافيا التجريبية.');
        $this->call('db:seed', ['--class' => GeographicSeeder::class, '--force' => true]);
    }
}
