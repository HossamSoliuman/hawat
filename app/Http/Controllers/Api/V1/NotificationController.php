<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NotificationResource;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * إشعارات الحساب الداخل — لكل الأدوار. الإشعار لغيره 404.
 */
class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = AppNotification::forUser($request->user())->with(['type', 'trip'])
            ->when($request->boolean('unread'), fn ($q) => $q->unread())
            ->orderByDesc('id')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return NotificationResource::collection($notifications)->additional([
            'meta' => ['unread_count' => AppNotification::forUser($request->user())->unread()->count()],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['data' => ['unread_count' => AppNotification::forUser($request->user())->unread()->count()]]);
    }

    public function read(Request $request, int $notification): NotificationResource
    {
        // إشعار حساب آخر 404.
        $model = AppNotification::forUser($request->user())->findOrFail($notification);
        $model->markRead();

        return new NotificationResource($model->load(['type', 'trip']));
    }

    public function readAll(Request $request): JsonResponse
    {
        $count = AppNotification::forUser($request->user())->unread()->update(['read_at' => now()]);

        return response()->json(['message' => 'عُلّمت كل الإشعارات مقروءة.', 'data' => ['marked' => $count]]);
    }
}
