@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @php
        $money = fn ($v) => number_format((float) $v, 2);
        $net = (float) $f['net_profit'];
    @endphp

    @include('panel.owner.reports.partials.head', ['print' => true])
    @include('panel.owner.reports.partials.closing-note', ['months' => $f['months_count'], 'closed' => $f['closed_count']])

    <div class="card">
        @include('partials.section-head', ['icon' => 'search', 'title' => 'الفلتر'])
        @include('panel.owner.reports.partials.filter', ['printable' => false, 'bare' => true])

        <div style="border-top:1px solid var(--hair);margin:1rem -1rem 0;padding:1rem 1rem 0">
            <h2 style="font-size:1.05rem;font-weight:700;margin-bottom:.9rem">ملخص الأرباح والخسائر</h2>

            <div class="stat-grid" style="margin-bottom:var(--gap)">
                @include('partials.stat-card', ['label' => 'إجمالي المبيعات', 'value' => $money($f['gross_sales']), 'unit' => 'ر.س', 'icon' => 'coins', 'tone' => 'success'])
                @include('partials.stat-card', ['label' => 'إجمالي المصاريف', 'value' => $money($f['total_expenses']), 'unit' => 'ر.س', 'icon' => 'receipt', 'tone' => 'danger'])
                @include('partials.stat-card', ['label' => 'الإهلاك', 'value' => $money($f['depreciation']), 'unit' => 'ر.س', 'icon' => 'trending-down', 'tone' => 'danger'])
            </div>
            <div class="stat-grid cols-4">
                @include('partials.stat-card', ['label' => 'صافي الربح', 'value' => $money(max($net, 0)), 'unit' => 'ر.س', 'icon' => 'trending-up', 'tone' => 'success'])
                @include('partials.stat-card', ['label' => 'الخسائر', 'value' => $money(max(-$net, 0)), 'unit' => 'ر.س', 'icon' => 'trending-down', 'tone' => 'danger'])
                @include('partials.stat-card', ['label' => 'حصة المالك'.($f['owner_percent'] !== null ? ' ('.\App\Services\Owner\OwnerReports::percent($f['owner_percent']).')' : ''), 'value' => $money($f['owner_share']), 'unit' => 'ر.س', 'icon' => 'user', 'tone' => 'success'])
                @include('partials.stat-card', ['label' => 'حصة البحارة', 'value' => $money($f['crew_share']), 'unit' => 'ر.س', 'icon' => 'users', 'tone' => 'warning'])
            </div>

            @if (count($f['crew_distribution']) > 0)
                <h2 style="font-size:1rem;font-weight:700;margin:1.4rem 0 .75rem">توزيع حصة الطاقم</h2>
                <div class="table-card" style="margin-bottom:1rem">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>البحار</th>
                                <th>الصفة</th>
                                <th>نسبة خاصة</th>
                                <th class="end">المستحق</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($f['crew_distribution'] as $member)
                                <tr>
                                    <td>{{ $member['name'] }}</td>
                                    <td>{{ $member['role'] }}</td>
                                    <td class="num">{{ $member['custom_percent'] !== null ? number_format($member['custom_percent'], 2).'%' : '-' }}</td>
                                    <td class="end" style="font-weight:700">{{ \App\Services\Owner\OwnerReports::money($member['due']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">حصة البحارة</td>
                                <td class="end">{{ \App\Services\Owner\OwnerReports::money(collect($f['crew_distribution'])->sum('due')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
