<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * أدوار تطبيق حوات الثمانية. المفاتيح ثابتة (App\Models\Role) لأن الشيفرة
 * تتفرّع عليها؛ الأسماء والترتيب هنا لأنها تُعرض وتُرتَّب. الكابتن لا دور
 * له: سجلّ صياد عند المالك، والمالك يدير الرحلة عنه.
 */
class RoleSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, name_en: string, description: string, has_portal: bool}>
     */
    public const ROLES = [
        Role::SUPER_ADMIN => [
            'name' => 'المدير العام',
            'name_en' => 'Super Admin',
            'description' => 'يدير حسابات التطبيق كلها ومركز المعلومات التشغيلي.',
            'has_portal' => true,
        ],
        Role::OWNER => [
            'name' => 'مالك القارب',
            'name_en' => 'Owner',
            'description' => 'ينشئ القوارب والكباتن والطاقم والرحلات، ويدير الرحلة من انطلاقها إلى مخرجاتها، ويبيع المصيد أو يرسله للدلال.',
            'has_portal' => true,
        ],
        Role::CREW => [
            'name' => 'فرد طاقم',
            'name_en' => 'Crew',
            'description' => 'عامل صيد على قارب المالك.',
            'has_portal' => false,
        ],
        Role::EMPLOYEE => [
            'name' => 'موظف',
            'name_en' => 'Employee',
            'description' => 'موظف إداري لدى المالك يتابع الرحلات والمبيعات.',
            'has_portal' => true,
        ],
        Role::COUNTER => [
            'name' => 'العدّاد',
            'name_en' => 'Counter',
            'description' => 'موظف الإحصاء في الميناء: يستلم المصيد ويعدّه ويؤكد وزنه.',
            'has_portal' => true,
        ],
        Role::DALAL => [
            'name' => 'الدلال',
            'name_en' => 'Dalal',
            'description' => 'يستلم المصيد من الملاك ويبيعه بعمولة ويصدر الفواتير.',
            'has_portal' => true,
        ],
        Role::MERCHANT => [
            'name' => 'تاجر السوق',
            'name_en' => 'Merchant',
            'description' => 'مشترٍ مسجّل في سوق السمك يزايد ويشتري.',
            'has_portal' => true,
        ],
        Role::COMPANY => [
            'name' => 'شركة التشغيل',
            'name_en' => 'Operating Company',
            'description' => 'تفتح التوظيف في موانئها وتعتمد العدّادين وتوقفهم وتنقلهم.',
            'has_portal' => true,
        ],
    ];

    public function run(): void
    {
        foreach (array_keys(self::ROLES) as $order => $key) {
            Role::updateOrCreate(['key' => $key], self::ROLES[$key] + [
                'display_order' => $order + 1,
                'active' => true,
            ]);
        }
    }
}
