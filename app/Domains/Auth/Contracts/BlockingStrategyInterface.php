<?php

namespace App\Domains\Auth\Contracts;

use App\Domains\Auth\DTOs\BlockingDecision;
use App\Domains\Auth\DTOs\LoginSecurityContext;

interface BlockingStrategyInterface
{
    public function check(LoginSecurityContext $context): ?BlockingDecision;

    public function getPriority(): int;
}
