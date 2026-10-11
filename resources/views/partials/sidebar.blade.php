@php
    use App\Support\Nav;

    $user = auth()->user();
    // قائمة المالك تُطوى أقسامها المعنونة: القسم <details> وعنوانه <summary> — انظر Nav::collapsible().
    $collapsible = Nav::collapsible();
@endphp
<aside class="sidebar" id="sidebar">
    {{-- الشعار انتقل إلى الشريط العلوي، فلم يبقَ في رأس القائمة إلا زرّ الإغلاق — ولا يظهر إلا دون 1024px. --}}
    <div class="sidebar-head">
        <button class="sidebar-close" onclick="toggleSidebar(false)">
            @include('partials.icon', ['name' => 'x'])
        </button>
    </div>

    <nav class="sidebar-nav">
        @foreach (Nav::sections() as $section)
            @php
                $current = collect($section['items'])->contains(fn (array $item) => Nav::isActive($item));
                $title = $section['title'] ?? null;
                $folds = $collapsible && $title !== null;
                $sectionTag = $folds ? 'details' : 'div';
                $titleTag = $folds ? 'summary' : 'p';
            @endphp
            <{{ $sectionTag }} @class(['nav-section', 'is-pinned' => $title === null]) @if ($folds) data-group="{{ $title }}" @if ($current) open data-current @endif @endif>
                @if ($title !== null)
                    <{{ $titleTag }} class="nav-section-title">
                        <span>{{ $title }}</span>
                        @if ($folds)
                            @include('partials.icon', ['name' => 'chevron-down'])
                        @endif
                    </{{ $titleTag }}>
                @endif
                @foreach ($section['items'] as $item)
                    <a href="{{ Nav::url($item) }}" class="nav-link {{ Nav::isActive($item) ? 'is-active' : '' }}">
                        @include('partials.icon', ['name' => $item['icon']])
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </{{ $sectionTag }}>
        @endforeach
    </nav>

    @if ($collapsible)
        {{--
            قسم الصفحة الحالية مفتوح دائمًا، وما فتحه المستخدم بيده يبقى مفتوحًا
            في الصفحات التالية. يُطبَّق هنا قبل الرسم فلا ترمش القائمة. الحفظ عند
            النقر لا عند حدث toggle: ذاك يُطلق أيضًا لقسم الصفحة المفتوح من الخادم،
            فتتراكم الأقسام المفتوحة بمجرّد زيارتها.
        --}}
        <script>
            (() => {
                const key = 'hawat-nav-open';
                const groups = document.querySelectorAll('#sidebar details[data-group]');
                let open = [];
                try { open = JSON.parse(localStorage.getItem(key)) || []; } catch (e) {}

                groups.forEach((group) => {
                    if (open.includes(group.dataset.group)) group.open = true;

                    group.querySelector('summary').addEventListener('click', () => {
                        const name = group.dataset.group;
                        open = open.filter((n) => n !== name);
                        if (!group.open) open.push(name);
                        try { localStorage.setItem(key, JSON.stringify(open)); } catch (e) {}
                    });
                });
            })();
        </script>
    @endif

    {{--
        ذيل القائمة: هويّة المستخدم — الداخل باسمه ودوره وزرّ خروجه، والزائر
        (بوابات الوزارة المفتوحة) بهويّة المشاهدة الافتراضية. البوابتان خلف
        الدخول لكلٍّ خروجها: تطبيق حوات إلى دخوله، وإدارة النظام إلى دخول
        بوابة المعلومات.
    --}}
    @php
        $logout = match (Nav::portalKey()) {
            Nav::OPS => 'panel.logout',
            Nav::SUBADMIN => 'logout',
            default => null,
        };
    @endphp
    <div class="sidebar-foot">
        <div class="user-chip">
            <div class="avatar">{{ $user?->initial ?? 'م' }}</div>
            <div class="meta">
                <p class="role">{{ $user?->name ?? 'مدير عام' }}</p>
                <p class="sub">{{ $user?->display_role ?? 'الإدارة العليا' }}</p>
            </div>
        </div>
        @if ($user && $logout)
            <form method="POST" action="{{ route($logout) }}">
                @csrf
                <button type="submit" class="icon-action" title="تسجيل الخروج" aria-label="تسجيل الخروج">
                    @include('partials.icon', ['name' => 'log-out'])
                </button>
            </form>
        @endif
    </div>
</aside>
