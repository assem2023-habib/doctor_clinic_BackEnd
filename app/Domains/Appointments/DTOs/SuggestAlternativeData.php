<?php

namespace App\Domains\Appointments\DTOs;

class SuggestAlternativeData
{
    public function __construct(
        public readonly string $message,
        public readonly string $changedBy,
    ) {}
}
