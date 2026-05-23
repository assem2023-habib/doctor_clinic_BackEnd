<?php

namespace App\Domains\Notifications\Actions;

use App\Domains\Notifications\Models\Notification;
use App\Models\User;

class MarkNotificationReadAction
{
    public function execute(User $user, Notification $notification): void
    {
        $user->notifications()->updateExistingPivot($notification->id, [
            'read_at' => now(),
        ]);
    }
}
