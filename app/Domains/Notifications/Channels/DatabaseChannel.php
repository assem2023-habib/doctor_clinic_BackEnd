<?php

namespace App\Domains\Notifications\Channels;

use App\Domains\Notifications\Contracts\NotificationChannelInterface;
use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Models\Notification;

class DatabaseChannel implements NotificationChannelInterface
{
    public function send(NotificationData $data): void
    {
        $notification = Notification::create([
            'topic' => $data->topic,
            'title' => $data->title,
            'body' => $data->body,
        ]);

        if (!empty($data->userIds)) {
            $notification->users()->attach($data->userIds);
        }
    }
}
