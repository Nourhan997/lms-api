<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(): JsonResponse
    {
        $user          = auth()->user();
        $notifications = $this->notificationService->getForUser($user);
        $unreadCount   = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'success' => true,
            'data'    => NotificationResource::collection($notifications),
            'message' => 'Notifications retrieved successfully.',
            'meta'    => [
                'total'        => $notifications->total(),
                'per_page'     => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    public function read(Notification $notification): JsonResponse
    {
        $notification = $this->notificationService->markAsRead($notification, auth()->user());

        return response()->json([
            'success' => true,
            'data'    => new NotificationResource($notification),
            'message' => 'Notification marked as read.',
            'meta'    => [],
        ]);
    }

    public function readAll(): JsonResponse
    {
        $this->notificationService->markAllAsRead(auth()->user());

        return response()->json([
            'success' => true,
            'data'    => null,
            'message' => 'All notifications marked as read.',
            'meta'    => [],
        ]);
    }

    public function destroy(Notification $notification): Response
    {
        $this->notificationService->delete($notification, auth()->user());

        return response()->noContent();
    }

    public function unreadCount(): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount(auth()->user());

        return response()->json([
            'success' => true,
            'data'    => ['count' => $count],
            'message' => 'Unread count retrieved.',
            'meta'    => [],
        ]);
    }
}
