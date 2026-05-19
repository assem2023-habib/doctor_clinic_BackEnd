<?php

namespace App\Domains\Auth\DTOs;

class BlockingDecision
{
    public function __construct(
        public readonly bool $blocked,
        public readonly string $reason,
        public readonly ?int $retryAfterSeconds = null,
        public readonly ?string $strategy = null,
    ) {}
}
