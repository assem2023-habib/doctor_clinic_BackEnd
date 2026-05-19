<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Contracts\NotificationChannelInterface;
use App\Domains\Notifications\DTOs\NotificationData;

class NotificationManager
{
    private array $channels = [];

    public function addChannel(string $name, NotificationChannelInterface $channel): void
    {
        $this->channels[$name] = $channel;
    }

    public function send(string $event, NotificationData $data): void
    {
        $eventChannels = config("notification.events.{$event}", []);

        foreach ($eventChannels as $channelName) {
            $channelConfig = config("notification.channels.{$channelName}");

            if (!($channelConfig['enabled'] ?? false)) {
                continue;
            }

            $channel = $this->channels[$channelName] ?? null;

            if ($channel === null) {
                continue;
            }

            $channel->send($data);
        }
    }
}
