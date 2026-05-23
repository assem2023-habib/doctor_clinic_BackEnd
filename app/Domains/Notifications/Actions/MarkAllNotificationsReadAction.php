<?php

namespace App\Domains\Notifications\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkAllNotificationsReadAction
{
    public function execute(User $user): void
    {
        DB::table('notification_user')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
