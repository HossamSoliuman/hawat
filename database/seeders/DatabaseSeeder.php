<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // الإنتاج يحمل بيانات حقيقية بعد hawat:purge-demo-data: لا يُعاد فيه إلا
        // ما تملكه الشيفرة من أدوار وقوائم مرجعية. البذّارات الأخرى تُنشئ
        // بيانات تجريبية وتعيد المناطق المحذوفة وعدّاداتها الوهمية.
        if (app()->isProduction()) {
            $this->call([RoleSeeder::class, LookupSeeder::class]);

            return;
        }

        $this->call([
            GeographicSeeder::class,
            FleetSeeder::class,
            SeasonsSeeder::class,
            OperationsSeeder::class,
            MarketsSeeder::class,
            GovernanceSeeder::class,
            SystemSeeder::class,
            RoleSeeder::class,
            // بعد SystemSeeder وRoleSeeder: حسابات الدخول تُنسب إلى الصلاحيات
            // التي يبذرها الأول وإلى أدوار التطبيق التي يبذرها الثاني.
            UserSeeder::class,
            SubAdministrationSeeder::class,
            ServicesLicensingSeeder::class,
            // قوائم بوابة المالك، ثم ربط المالك التجريبي بقاربين ورحلاتهما.
            LookupSeeder::class,
            DemoOwnerSeeder::class,
            // شركة تشغيل تجريبية توظّف العدّادين في ميناء المالك التجريبي.
            DemoCompanySeeder::class,
        ]);
    }
}
