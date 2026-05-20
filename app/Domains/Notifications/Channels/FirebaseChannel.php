<?php

namespace App\Domains\Notifications\Channels;

use App\Domains\Notifications\Contracts\NotificationChannelInterface;
use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Services\FirebaseService;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly FirebaseService $firebase,
    ) {}

    public function send(NotificationData $data): void
    {
        if (!$this->firebase->isAvailable()) {
            return;
        }

        if (empty($data->userIds)) {
            return;
        }

        $tokens = User::whereIn('id', $data->userIds)
            ->whereNotNull('fcm_tokens')
            ->pluck('fcm_tokens')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $messaging = $this->firebase->messaging();

        if ($messaging === null) {
            return;
        }

        try {
            $notification = Notification::create($data->title, $data->body['message'] ?? $data->title);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData(array_merge($data->body, [
                    'topic' => $data->topic,
                    'type' => $data->type ?? $data->topic,
                ]));

            $report = $messaging->sendMulticast($message, $tokens);

            if ($report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    Log::error('FCM send failed', [
                        'token' => $failure->target()->value(),
                        'error' => $failure->error()->getMessage(),
                    ]);
                }
            }
        } catch (FirebaseException $e) {
            Log::error('FCM FirebaseException', ['message' => $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('FCM exception', ['message' => $e->getMessage()]);
        }
    }
}
