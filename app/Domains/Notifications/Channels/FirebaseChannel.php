<?php

namespace App\Domains\Notifications\Channels;

use App\Domains\Notifications\Contracts\NotificationChannelInterface;
use App\Domains\Notifications\DTOs\NotificationData;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseChannel implements NotificationChannelInterface
{
    private ?string $serverKey;

    public function __construct()
    {
        $this->serverKey = config('notification.channels.firebase.server_key');
    }

    public function send(NotificationData $data): void
    {
        if (empty($this->serverKey)) {
            Log::warning('FCM server key not configured');
            return;
        }

        if (empty($data->userIds)) {
            return;
        }

        $tokens = User::whereIn('id', $data->userIds)
            ->whereNotNull('device_tokens')
            ->pluck('device_tokens')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $payload = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $data->title,
                'body' => $data->body['message'] ?? $data->title,
            ],
            'data' => array_merge($data->body, [
                'topic' => $data->topic,
                'type' => $data->type ?? $data->topic,
            ]),
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => "key={$this->serverKey}",
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if (!$response->successful()) {
                Log::error('FCM send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('FCM exception', ['message' => $e->getMessage()]);
        }
    }
}
