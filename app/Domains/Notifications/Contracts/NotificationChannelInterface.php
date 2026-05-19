<?php

namespace App\Domains\Notifications\Contracts;

use App\Domains\Notifications\DTOs\NotificationData;

interface NotificationChannelInterface
{
    public function send(NotificationData $data): void;
}
