<?php

namespace App\Domains\Notifications\Controllers;

use App\Domains\Notifications\Actions\MarkAllNotificationsReadAction;
use App\Domains\Notifications\Actions\MarkMultipleNotificationsReadAction;
use App\Domains\Notifications\Actions\MarkNotificationReadAction;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Requests\DeleteNotificationsRequest;
use App\Domains\Notifications\Requests\MarkNotificationsReadRequest;
use App\Domains\Notifications\Resources\NotificationResource;
use App\Domains\Shared\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    public function __construct(
        private readonly MarkNotificationReadAction $markReadAction,
        private readonly MarkMultipleNotificationsReadAction $markMultipleReadAction,
        private readonly MarkAllNotificationsReadAction $markAllReadAction,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->getUser($request);
        $limit = (int) $request->integer('limit', 20);

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(min($limit, 100));

        $unreadCount = $user->notifications()
            ->wherePivotNull('read_at')
            ->count();

        return ApiResponse::success(
            data: [
                'notifications' => NotificationResource::collection($notifications),
                'unread_count' => $unreadCount,
            ],
            message: __('Notifications retrieved successfully'),
            pagination: ApiResponse::pagination($notifications),
        );
    }

    public function show(Request $request, Notification $notification): JsonResponse
    {
        $user = $this->getUser($request);

        $notification = $user->notifications()->find($notification->id);

        if (!$notification) {
            return ApiResponse::notFound(__('Notification not found'));
        }

        if (is_null($notification->pivot?->read_at)) {
            $this->markReadAction->execute($user, $notification);
            $notification->pivot->read_at = now();
        }

        return ApiResponse::success(
            new NotificationResource($notification),
            __('Notification retrieved successfully')
        );
    }

    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        $user = $this->getUser($request);

        $notification = $user->notifications()->find($notification->id);

        if (!$notification) {
            return ApiResponse::notFound(__('Notification not found'));
        }

        $this->markReadAction->execute($user, $notification);

        return ApiResponse::success(null, __('Notification marked as read'));
    }

    public function markMultipleAsRead(MarkNotificationsReadRequest $request): JsonResponse
    {
        $user = $this->getUser($request);

        $this->markMultipleReadAction->execute($user, $request->input('ids'));

        return ApiResponse::success(null, __('Notifications marked as read'));
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $this->markAllReadAction->execute($user);

        return ApiResponse::success(null, __('All notifications marked as read'));
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        $user = $this->getUser($request);

        $exists = $user->notifications()
            ->where('notification_id', $notification->id)
            ->exists();

        if (!$exists) {
            return ApiResponse::notFound(__('Notification not found'));
        }

        $user->notifications()->detach($notification->id);

        return ApiResponse::success(null, __('Notification deleted'));
    }

    public function destroyMultiple(DeleteNotificationsRequest $request): JsonResponse
    {
        $user = $this->getUser($request);

        $user->notifications()->detach($request->input('ids'));

        return ApiResponse::success(null, __('Notifications deleted'));
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $user = $this->getUser($request);

        $user->notifications()->detach();

        return ApiResponse::success(null, __('All notifications deleted'));
    }

    private function getUser(Request $request): User
    {
        return $request->user();
    }
}
