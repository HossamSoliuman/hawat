@php
    $badgeMap = $resource['badges'] ?? [];
    $columns = $resource['columns'];
    $readonly = ! empty($resource['readonly']);
    $updateTemplate = route('admin.resource.update', ['tab' => $activeTab, 'resource' => $activeResource, 'id' => '__ID__']);
    $fieldKeys = collect($fields)->pluck('key')->all();

    // حقول التاريخ تُحوَّل إلى Y-m-d لأن <input type="date"> لا يقبل صيغة ISO الكاملة.
    $editPayload = fn ($record) => collect($record->only($fieldKeys))
        ->map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value)
        ->all();

    // أول عمود في الجدول اسم السجل — يُذكر في رسالة تأكيد الحذف.
    $nameColumn = array_key_first($columns);
@endphp

@if (count($resources) > 1)
    <nav class="restabs" aria-label="جداول التبويب">
        @foreach ($resources as $key => $item)
            <a class="restab @if ($key === $activeResource) is-active @endif"
               href="{{ route('admin.tab', ['tab' => $activeTab, 'resource' => $key]) }}"
               @if ($key === $activeResource) aria-current="page" @endif>
                {{ $item['label'] }}
                <span class="count">{{ number_format($counts[$key]) }}</span>
            </a>
        @endforeach
    </nav>
@endif

<div class="panel-body">
    <div class="toolbar">
        <div>
            <h2>{{ $resource['title'] }}</h2>
        </div>
        <div class="tools">
            <form class="search" method="GET" action="{{ route('admin.tab', $activeTab) }}" role="search">
                <input type="hidden" name="resource" value="{{ $activeResource }}">
                @include('partials.icon', ['name' => 'search'])
                <input class="input" type="search" name="q" value="{{ $search }}" placeholder="بحث في {{ $resource['label'] }}…" aria-label="بحث">
            </form>
            @unless ($readonly)
                <button type="button" class="btn btn-primary" data-record-create>
                    @include('partials.icon', ['name' => 'plus'])
                    إضافة سجل
                </button>
            @endunless
        </div>
    </div>

    <div class="table-card">
        @if ($records->isEmpty())
            <div class="empty-state">
                <span class="glyph">@include('partials.icon', ['name' => $search !== '' ? 'search' : 'inbox'])</span>
                @if ($search !== '')
                    <h3>لا نتائج لـ «{{ $search }}»</h3>
                    <p>جرّب كلمة أقصر أو امسح البحث لعرض كل السجلات.</p>
                    <a class="btn btn-outline" href="{{ route('admin.tab', ['tab' => $activeTab, 'resource' => $activeResource]) }}">مسح البحث</a>
                @else
                    <h3>لا توجد سجلات بعد</h3>
                    <p>أضف أول سجل في {{ $resource['label'] }} ليظهر هنا وفي صفحات اللوحة التي تقرأ منه.</p>
                    @unless ($readonly)
                        <button type="button" class="btn btn-primary" data-record-create>
                            @include('partials.icon', ['name' => 'plus'])
                            إضافة سجل
                        </button>
                    @endunless
                @endif
            </div>
        @else
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach ($columns as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        @unless ($readonly)
                            <th style="text-align:center;">إجراءات</th>
                        @endunless
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr>
                            @foreach ($columns as $column => $label)
                                <td>
                                    @include('admin.partials.cell', [
                                        'record' => $record,
                                        'column' => $column,
                                        'badgeMap' => $badgeMap,
                                    ])
                                </td>
                            @endforeach
                            @unless ($readonly)
                                <td class="cell-actions">
                                    <button type="button" class="icon-action" title="تعديل" aria-label="تعديل"
                                            data-record-edit="{{ $record->id }}"
                                            data-record='@json($editPayload($record))'>
                                        @include('partials.icon', ['name' => 'pencil'])
                                    </button>
                                    <button type="button" class="icon-action danger" title="حذف" aria-label="حذف"
                                            data-record-delete="{{ route('admin.resource.destroy', ['tab' => $activeTab, 'resource' => $activeResource, 'id' => $record->id]) }}"
                                            data-record-name="{{ data_get($record, $nameColumn) }}">
                                        @include('partials.icon', ['name' => 'trash'])
                                    </button>
                                </td>
                            @endunless
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="pager">
        <span class="range">
            @if ($records->total() > 0)
                {{ number_format($records->firstItem()) }}–{{ number_format($records->lastItem()) }} من {{ number_format($records->total()) }} سجل
            @else
                0 سجل
            @endif
        </span>
        {{ $records->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
</div>

@unless ($readonly)
    <dialog class="modal" id="record-modal" aria-labelledby="record-modal-title">
        <form method="POST" id="record-form"
              action="{{ route('admin.resource.store', ['tab' => $activeTab, 'resource' => $activeResource]) }}">
            @csrf
            <input type="hidden" name="_method" value="POST" id="record-method">

            <div class="modal-head">
                <div>
                    <h3 id="record-modal-title">إضافة سجل</h3>
                    <p>{{ $resource['title'] }}</p>
                </div>
                <button type="button" class="icon-action" data-modal-close aria-label="إغلاق">
                    @include('partials.icon', ['name' => 'x'])
                </button>
            </div>

            <div class="modal-body">
                <div class="form-grid">
                    @foreach ($fields as $field)
                        @include('admin.partials.field', ['field' => $field])
                    @endforeach
                </div>
            </div>

            <div class="modal-foot">
                <button type="submit" class="btn btn-primary">
                    @include('partials.icon', ['name' => 'save'])
                    حفظ
                </button>
                <button type="button" class="btn btn-outline" data-modal-close>إلغاء</button>
            </div>
        </form>
    </dialog>

    {{-- تأكيد الحذف نافذةٌ من البوابة نفسها لا confirm() المتصفّح، ويُسمّى فيها السجل. --}}
    <dialog class="modal sm" id="delete-modal" aria-labelledby="delete-modal-title">
        <form method="POST" id="delete-form">
            @csrf
            @method('DELETE')

            <div class="modal-head">
                <h3 id="delete-modal-title">حذف السجل</h3>
                <button type="button" class="icon-action" data-modal-close aria-label="إغلاق">
                    @include('partials.icon', ['name' => 'x'])
                </button>
            </div>

            <div class="modal-body">
                <p>سيُحذف «<strong id="delete-name"></strong>» من {{ $resource['label'] }} نهائيًا، ويُسجَّل الحذف باسمك في سجل العمليات.</p>
            </div>

            <div class="modal-foot">
                <button type="submit" class="btn btn-danger">
                    @include('partials.icon', ['name' => 'trash'])
                    حذف
                </button>
                <button type="button" class="btn btn-outline" data-modal-close>إلغاء</button>
            </div>
        </form>
    </dialog>

    @push('scripts')
        <script>
            (function () {
                const modal = document.getElementById('record-modal');
                const form = document.getElementById('record-form');
                const method = document.getElementById('record-method');
                const title = document.getElementById('record-modal-title');
                const storeAction = form.getAttribute('action');
                const updateTemplate = @json($updateTemplate);

                function resetForm() {
                    form.reset();
                    form.querySelectorAll('[type=checkbox]').forEach((box) => { box.checked = false; });
                }

                function fill(values) {
                    Object.entries(values).forEach(([key, value]) => {
                        const input = form.querySelector('[data-field="' + key + '"]');
                        if (!input) return;
                        if (input.type === 'checkbox') {
                            input.checked = Boolean(value);
                        } else {
                            input.value = value === null || value === undefined ? '' : value;
                        }
                    });
                }

                function open(dialog) {
                    dialog.showModal();
                    dialog.querySelector('.modal-body input:not([type=hidden]), .modal-body select, .modal-body textarea, .btn-danger')?.focus();
                }

                document.querySelectorAll('[data-record-create]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        resetForm();
                        form.setAttribute('action', storeAction);
                        method.value = 'POST';
                        title.textContent = 'إضافة سجل';
                        open(modal);
                    });
                });

                document.querySelectorAll('[data-record-edit]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        resetForm();
                        form.setAttribute('action', updateTemplate.replace('__ID__', button.dataset.recordEdit));
                        method.value = 'PUT';
                        title.textContent = 'تعديل سجل';
                        fill(JSON.parse(button.dataset.record));
                        open(modal);
                    });
                });

                const deleteModal = document.getElementById('delete-modal');
                document.querySelectorAll('[data-record-delete]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        document.getElementById('delete-form').setAttribute('action', button.dataset.recordDelete);
                        document.getElementById('delete-name').textContent = button.dataset.recordName || 'السجل';
                        open(deleteModal);
                    });
                });

                document.querySelectorAll('[data-modal-close]').forEach(function (button) {
                    button.addEventListener('click', () => button.closest('dialog').close());
                });

                // النقر على الخلفية خارج النافذة يغلقها كما يغلقها Esc.
                [modal, deleteModal].forEach(function (dialog) {
                    dialog.addEventListener('click', function (event) {
                        if (event.target === dialog) dialog.close();
                    });
                });

                // خطأ تحقّق في الإضافة يعود بالصفحة، فيُعاد فتح النموذج بقيمه القديمة.
                @if ($errors->any() && old('_method') === 'POST')
                    open(modal);
                @endif
            })();
        </script>
    @endpush
@endunless
