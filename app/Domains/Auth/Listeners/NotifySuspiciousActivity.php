<?php

namespace App\Domains\Auth\Listeners;

use App\Domains\Auth\Events\SuspiciousLoginAttempts;
use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Services\NotificationManager;
use Illuminate\Support\Facades\Log;

class NotifySuspiciousActivity
{
    public function __construct(
        private readonly NotificationManager $notificationManager,
    ) {}

    public function handle(SuspiciousLoginAttempts $event): void
    {
        Log::warning('Suspicious login activity detected', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip' => $event->ip,
            'failed_attempts' => $event->failedAttempts,
            'different_devices' => $event->differentDevices,
        ]);

        $this->notificationManager->send('login.suspicious_activity', new NotificationData(
            topic: 'login.suspicious_activity',
            title: __('auth.suspicious_activity_subject'),
            body: [
                'user_name' => $event->user->first_name . ' ' . $event->user->last_name,
                'ip' => $event->ip,
                'failed_attempts' => $event->failedAttempts,
                'different_devices' => $event->differentDevices,
                'time' => now()->toIso8601String(),
                'recommendation' => __('auth.suspicious_activity_recommendation'),
            ],
            userIds: [$event->user->id],
            type: 'login_security',
        ));
    }
}
