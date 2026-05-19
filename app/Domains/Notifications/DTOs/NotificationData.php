<?php

namespace App\Domains\Notifications\DTOs;

class NotificationData
{
    public function __construct(
        public readonly string $topic,
        public readonly string $title,
        public readonly array $body,
        public readonly array $userIds = [],
        public readonly ?string $type = null,
    ) {}
}
