<?php

namespace App\Domains\Notifications\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkMultipleNotificationsReadAction
{
    public function execute(User $user, array $notificationIds): void
    {
        DB::table('notification_user')
            ->where('user_id', $user->id)
            ->whereIn('notification_id', $notificationIds)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
