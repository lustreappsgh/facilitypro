<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Notification $notification): bool
    {
        return $notification->notifiable_id === $user->id
            && $notification->notifiable_type === $user->getMorphClass();
    }

    public function update(User $user, Notification $notification): bool
    {
        return $notification->notifiable_id === $user->id
            && $notification->notifiable_type === $user->getMorphClass();
    }
}
