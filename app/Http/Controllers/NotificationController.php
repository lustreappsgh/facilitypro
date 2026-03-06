<?php

namespace App\Http\Controllers;

use App\Domains\Notifications\Actions\MarkAllNotificationsReadAction;
use App\Domains\Notifications\Actions\MarkNotificationReadAction;
use App\Domains\Notifications\DTOs\NotificationReadAllData;
use App\Domains\Notifications\DTOs\NotificationReadData;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function __construct(
        protected MarkNotificationReadAction $markNotificationReadAction,
        protected MarkAllNotificationsReadAction $markAllNotificationsReadAction
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Notification $notification) => $this->serializeNotification($notification));

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification = $this->markNotificationReadAction->execute(
            new NotificationReadData($notification->id)
        );

        return response()->json([
            'notification' => $notification ? $this->serializeNotification($notification) : null,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Notification::class);

        $this->markAllNotificationsReadAction->execute(
            new NotificationReadAllData($request->user()->id)
        );

        return response()->json([
            'unread_count' => 0,
        ]);
    }

    protected function serializeNotification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => Carbon::parse($notification->created_at)->toIso8601String(),
        ];
    }
}
