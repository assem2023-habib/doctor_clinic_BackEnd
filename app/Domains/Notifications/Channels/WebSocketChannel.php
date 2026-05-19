<?php

namespace App\Domains\Notifications\Channels;

use App\Domains\Notifications\Contracts\NotificationChannelInterface;
use App\Domains\Notifications\DTOs\NotificationData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebSocketChannel implements NotificationChannelInterface
{
    public function send(NotificationData $data): void
    {
        $reverbHost = config('notification.channels.websocket.host', 'localhost');
        $reverbPort = config('notification.channels.websocket.port', 8080);
        $appKey = config('notification.channels.websocket.app_key');
        $appSecret = config('notification.channels.websocket.app_secret');

        if (empty($appKey) || empty($appSecret)) {
            Log::warning('Reverb app key or secret not configured');
            return;
        }

        $eventName = str_replace('.', '_', $data->topic);

        $payload = [
            'event' => $eventName,
            'data' => [
                'topic' => $data->topic,
                'title' => $data->title,
                'body' => $data->body,
                'user_ids' => $data->userIds,
                'type' => $data->type ?? $data->topic,
            ],
            'channels' => $this->buildChannels($data->userIds),
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-App-Key' => $appKey,
                'X-App-Secret' => $appSecret,
            ])->post("http://{$reverbHost}:{$reverbPort}/apps/{$appKey}/events", $payload);

            if (!$response->successful()) {
                Log::error('Reverb send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Reverb exception', ['message' => $e->getMessage()]);
        }
    }

    private function buildChannels(array $userIds): array
    {
        $channels = ['global'];

        foreach ($userIds as $userId) {
            $channels[] = "user.{$userId}";
        }

        return $channels;
    }
}
