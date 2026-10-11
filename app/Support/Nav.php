<?php

namespace App\Support;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * يحدّد البوابة النشطة داخل لوحة الوزارة وقائمتها الجانبية.
 *
 * أربع بوابات تتشارك التخطيط نفسه: بوابات الوزارة الثلاث على مضيفها
 * (config/hawat.php → gov_domain) — قسم الإحصاء تحت /stats ومعه شاشة العرض تحت
 * /screens، وقسم الخدمات والتراخيص تحت /services، وإدارة النظام ببوابة المعلومات
 * تحت /subadmin — ثم تطبيق حوات على النطاق الرئيسي تحت /admin. التمييز من اسم
 * المسار لا من المسار نفسه: مسارات بوابات الوزارة تحمل بادئاتها، ومسارات
 * تطبيق حوات إمّا بالبادئة panel. أو بلا بادئة (مركز العمليات).
 *
 * تطبيق حوات خلف دخوله وقائمته تتبدّل مع دور المستخدم (config/hawat.php →
 * nav_panel). وإدارة النظام خلف دخولها على /login.
 */
class Nav
{
    public const STATS = 'stats';

    public const SERVICES = 'services';

    public const SUBADMIN = 'subadmin';

    public const OPS = 'ops';

    /**
     * مفتاح كل بوابة ومصدر قائمتها، بترتيب عرضها. تطبيق حوات آخرها
     * لأنه الافتراضي: يلتقط كل مسار لم يطابق بادئة قبله، وقائمته تُبنى في
     * panelSections() لا تُقرأ من مفتاح واحد.
     */
    private const SECTIONS = [
        self::STATS => 'hawat.nav_stats',
        self::SERVICES => 'hawat.nav_services',
        self::SUBADMIN => 'hawat.nav_subadmin',
        self::OPS => 'hawat.nav',
    ];

    /**
     * بادئات أسماء المسارات لكل بوابة. شاشة العرض (screens.) من الإحصاء، وبوابة
     * المعلومات (admin.) من إدارة النظام.
     */
    private const PREFIXES = [
        'screens.' => self::STATS,
        'stats.' => self::STATS,
        'services.' => self::SERVICES,
        'subadmin.' => self::SUBADMIN,
        'admin.' => self::SUBADMIN,
    ];

    /**
     * مفاتيح البوابات بترتيب عرضها.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::SECTIONS);
    }

    /**
     * مفتاح البوابة النشطة في الطلب الحالي.
     */
    public static function portalKey(): string
    {
        $route = Route::currentRouteName() ?? '';

        foreach (self::PREFIXES as $prefix => $key) {
            if (str_starts_with($route, $prefix)) {
                return $key;
            }
        }

        return self::OPS;
    }

    /**
     * بيانات البوابة النشطة (الاسم، الأيقونة، مسار الرئيسية).
     *
     * @return array{label: string, icon: string, home: string}
     */
    public static function portal(?string $key = null): array
    {
        return config('hawat.portals.'.($key ?? self::portalKey()));
    }

    /**
     * هل الطلب على لوحة من لوحات شاشة العرض (/screens)؟
     */
    public static function onScreens(): bool
    {
        return str_starts_with(Route::currentRouteName() ?? '', 'screens.');
    }

    /**
     * وضع العرض: تُطوى القائمة الجانبية والشريط العلوي ويُكبَّر القياس.
     *
     * لوحات شاشة العرض تُفتح عليه افتراضًا لأنها تُعرض على شاشة قاعة، وبقية
     * الصفحات لا تدخله إلا بطلبه في ?screen=1. و?screen=0 يعيد التخطيط الكامل.
     */
    public static function screenMode(): bool
    {
        return request()->boolean('screen', self::onScreens());
    }

    /**
     * وجهة زرّ الرجوع في وضع العرض: شاشة الاختيار للوحات القاعة، ورئيسة
     * البوابة لغيرها.
     */
    public static function screenHome(): string
    {
        return self::onScreens() ? 'screens.home' : self::portal()['home'];
    }

    /**
     * لوحات شاشة العرض — مربّعات شاشة الاختيار على /screens.
     */
    public static function screens(): array
    {
        return config('hawat.nav_screens');
    }

    /**
     * أقسام القائمة الجانبية للبوابة النشطة.
     */
    public static function sections(?string $key = null): array
    {
        $key ??= self::portalKey();

        if ($key === self::OPS) {
            return self::panelSections();
        }

        return array_map(fn (array $section) => [
            'title' => $section['title'],
            'items' => array_map(self::expand(...), $section['items']),
        ], config(self::SECTIONS[$key]));
    }

    /**
     * رابط عنصر من القائمة.
     */
    public static function url(array $item): string
    {
        return route($item['route'], $item['params'] ?? []);
    }

    /**
     * هل عنصر القائمة هو الصفحة الحالية؟ 'active' نمطٌ — أو قائمة أنماط — يجمع
     * صفحات تحت تبويب واحد (كلوحات شاشة العرض وتبويبات إعدادات المالك)،
     * و'params' تميّز تبويبات المسار الواحد.
     */
    public static function isActive(array $item): bool
    {
        if (! request()->routeIs(...(array) ($item['active'] ?? $item['route']))) {
            return false;
        }

        foreach ($item['params'] ?? [] as $name => $value) {
            if (request()->route($name) !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     * عنصر بـ 'tab' تبويبٌ من بوابة المعلومات: اسمه وأيقونته من config/info.php
     * ورابطه مساره هناك.
     */
    private static function expand(array $item): array
    {
        if (! isset($item['tab'])) {
            return $item;
        }

        $tab = config('info.tabs.'.$item['tab']);

        return $item + [
            'label' => $tab['label'],
            'icon' => $tab['icon'],
            'route' => 'admin.tab',
            'params' => ['tab' => $item['tab']],
        ];
    }

    /**
     * دور التطبيق الذي تُبنى عليه قائمة تطبيق حوات. الزائر يرى قائمة المدير
     * العام لأنها الأشمل.
     */
    public static function panelRole(): string
    {
        return Auth::user()?->app_role_key ?? Role::SUPER_ADMIN;
    }

    /**
     * هل تُطوى أقسام القائمة؟ قائمة المالك وحدها طويلة بما يفيض عن الشاشة،
     * فكل قسم فيها ينطوي، ولا يُفتح افتراضًا إلا قسم الصفحة الحالية.
     */
    public static function collapsible(): bool
    {
        return self::portalKey() === self::OPS && self::panelRole() === Role::OWNER;
    }

    /**
     * قائمة تطبيق حوات لدور بعينه: أقسام الدور من nav_panel، ويُلحق بالمدير
     * العام مركز العمليات (nav) لأنه قسمه.
     */
    public static function panelSections(?string $role = null): array
    {
        $role ??= self::panelRole();

        $sections = config('hawat.nav_panel.'.$role, []);

        if ($role === Role::SUPER_ADMIN) {
            $sections = array_merge($sections, config(self::SECTIONS[self::OPS]));
        }

        return $sections;
    }

    /**
     * عنوان الصفحة المقابل لاسم مسار، بالبحث في قوائم البوابات كلها ولوحات
     * شاشة العرض — وفي تطبيق حوات قوائم الأدوار كلها لا قائمة الدور الحالي.
     */
    public static function label(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        $groups = [self::screens()];

        foreach (self::keys() as $key) {
            if ($key === self::OPS) {
                foreach (array_keys(config('hawat.nav_panel', [])) as $role) {
                    $groups[] = self::panelSections($role);
                }

                continue;
            }

            $groups[] = self::sections($key);
        }

        foreach ($groups as $sections) {
            foreach ($sections as $section) {
                foreach ($section['items'] as $item) {
                    // تبويبات بوابة المعلومات تتشارك مسارًا واحدًا، فلا يُعرف أحدها من اسمه.
                    if ($item['route'] === $routeName && ! isset($item['params'])) {
                        return $item['label'];
                    }
                }
            }
        }

        return null;
    }
}
