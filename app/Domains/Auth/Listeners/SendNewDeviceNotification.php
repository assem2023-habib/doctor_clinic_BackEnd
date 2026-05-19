<?php

namespace App\Domains\Auth\Listeners;

use App\Domains\Auth\Events\LoginFromNewDevice;
use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Services\NotificationManager;
use Illuminate\Support\Facades\Log;

class SendNewDeviceNotification
{
    public function __construct(
        private readonly NotificationManager $notificationManager,
    ) {}

    public function handle(LoginFromNewDevice $event): void
    {
        Log::info('Login from new device detected', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'ip' => $event->ip,
            'device_fingerprint' => $event->deviceFingerprint,
            'user_agent' => $event->userAgent,
        ]);

        $this->notificationManager->send('login.new_device', new NotificationData(
            topic: 'login.new_device',
            title: __('auth.new_device_login_subject'),
            body: [
                'user_name' => $event->user->first_name . ' ' . $event->user->last_name,
                'ip' => $event->ip,
                'device' => $event->userAgent ?? 'Unknown device',
                'time' => now()->toIso8601String(),
            ],
            userIds: [$event->user->id],
            type: 'login_security',
        ));
    }
}
