<?php

namespace App\Domains\Notifications\Channels;

use App\Domains\Notifications\Contracts\NotificationChannelInterface;
use App\Domains\Notifications\DTOs\NotificationData;
use Illuminate\Support\Facades\Log;

class LogChannel implements NotificationChannelInterface
{
    public function send(NotificationData $data): void
    {
        Log::info("[{$data->topic}] {$data->title}", [
            'topic' => $data->topic,
            'body' => $data->body,
            'user_ids' => $data->userIds,
            'type' => $data->type,
        ]);
    }
}
