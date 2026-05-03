<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function getForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->inAppNotifications()
            ->latest()
            ->paginate($perPage);
    }

    public function markAsRead(Notification $notification, User $user): Notification
    {
        if ($notification->user_id !== $user->id) {
            throw new AuthorizationException();
        }

        $notification->update(['read_at' => now()]);

        return $notification;
    }

    public function markAllAsRead(User $user): void
    {
        $user->inAppNotifications()
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function getUnreadCount(User $user): int
    {
        return $user->inAppNotifications()->unread()->count();
    }

    public function create(User $user, string $type, string $title, string $body, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => empty($data) ? null : $data,
        ]);
    }

    public function delete(Notification $notification, User $user): void
    {
        if ($notification->user_id !== $user->id) {
            throw new AuthorizationException();
        }

        $notification->delete();
    }
}
