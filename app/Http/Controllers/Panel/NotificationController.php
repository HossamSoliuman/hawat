<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\DalalPayout;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * شاشة الإشعارات — لكل حساب تطبيق أيًّا كان دوره. فتح الإشعار يعلّمه مقروءًا
 * ويذهب إلى شاشته في بوابة هذا الدور (الرحلة، أو المخزون والطلبات للدلال).
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('panel.notifications.index', [
            'notifications' => AppNotification::forUser($user)->with(['type', 'trip'])
                ->when($request->query('filter') === 'unread', fn ($q) => $q->unread())
                ->orderByDesc('id')
                ->paginate(25)->withQueryString(),
            'unread' => AppNotification::forUser($user)->unread()->count(),
            'total' => AppNotification::forUser($user)->count(),
        ]);
    }

    public function read(Request $request, int $notification): RedirectResponse
    {
        // إشعار حساب آخر 404.
        $model = AppNotification::forUser($request->user())->findOrFail($notification);
        $model->markRead();

        return redirect($this->targetUrl($request->user(), $model) ?? route('panel.notifications'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        AppNotification::forUser($request->user())->unread()->update(['read_at' => now()]);

        return redirect()->route('panel.notifications')->with('status', 'عُلّمت كل الإشعارات مقروءة.');
    }

    /**
     * الشاشة التي يفتحها الإشعار في بوابة الدور: شاشة `data.target` إن ذُكرت
     * (مخزون الدلال، طلبات الملاك، الدلالون عند المالك)، وإلا صفحة الرحلة.
     */
    private function targetUrl(User $user, AppNotification $notification): ?string
    {
        $data = $notification->data ?? [];
        $payout = isset($data['payout_id']) ? DalalPayout::find($data['payout_id']) : null;

        $target = match ([$user->app_role_key, $data['target'] ?? null]) {
            [Role::DALAL, 'stock'] => route('panel.dalal.stock'),
            [Role::DALAL, 'partnerships'] => route('panel.dalal.requests'),
            [Role::DALAL, 'owners'] => route('panel.dalal.owners'),
            [Role::DALAL, 'sale'] => route('panel.dalal.sales.show', $data['sale_id']),
            [Role::COMPANY, 'applications'] => route('panel.company.applications'),
            [Role::OWNER, 'dalal_invoice'] => route('panel.owner.dalal-invoices.show', $data['sale_id']),
            // بيع من مصيده يفتح فاتورته، ودفعة الدلال تفتح كشف حسابه عنده.
            [Role::OWNER, 'dalals'] => match (true) {
                isset($data['sale_id']) => route('panel.owner.dalal-invoices.show', $data['sale_id']),
                $payout !== null => route('panel.owner.dalal-accounts.show', $payout->dalal_id),
                default => route('panel.owner.dalals'),
            },
            default => null,
        };

        if ($target !== null || $notification->trip_id === null) {
            return $target;
        }

        return match ($user->app_role_key) {
            Role::OWNER => route('panel.owner.trips.show', $notification->trip_id),
            Role::COUNTER => route('panel.counter.trips.show', $notification->trip_id),
            default => null,
        };
    }
}
