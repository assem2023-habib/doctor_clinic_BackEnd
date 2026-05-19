<?php

namespace App\Domains\Notifications\Channels;

use App\Domains\Notifications\Contracts\NotificationChannelInterface;
use App\Domains\Notifications\DTOs\NotificationData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocketIOChannel implements NotificationChannelInterface
{
    public function send(NotificationData $data): void
    {
        $serverUrl = config('notification.channels.socketio.server_url');
        $secret = config('notification.channels.socketio.secret');

        if (empty($serverUrl)) {
            Log::warning('Socket.IO server URL not configured');
            return;
        }

        $payload = [
            'secret' => $secret,
            'event' => str_replace('.', '_', $data->topic),
            'data' => [
                'topic' => $data->topic,
                'title' => $data->title,
                'body' => $data->body,
                'user_ids' => $data->userIds,
                'type' => $data->type ?? $data->topic,
            ],
        ];

        try {
            $response = Http::timeout(5)->post("{$serverUrl}/emit", $payload);

            if (!$response->successful()) {
                Log::error('Socket.IO send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Socket.IO exception', ['message' => $e->getMessage()]);
        }
    }
}
