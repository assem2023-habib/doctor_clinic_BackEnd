<?php

namespace App\Domains\Auth\Strategies;

use App\Domains\Auth\Contracts\BlockingStrategyInterface;
use App\Domains\Auth\Contracts\LoginAttemptRepositoryInterface;
use App\Domains\Auth\DTOs\BlockingDecision;
use App\Domains\Auth\DTOs\LoginSecurityContext;

class SuspiciousActivityStrategy implements BlockingStrategyInterface
{
    public function __construct(
        private readonly LoginAttemptRepositoryInterface $repository,
    ) {}

    public function check(LoginSecurityContext $context): ?BlockingDecision
    {
        if ($context->deviceFingerprint === null) {
            return null;
        }

        $differentDevices = $this->repository->countRecentFailuresByEmailFromDifferentFingerprints(
            $context->email,
            10
        );

        $threshold = config('login-security.limits.max_unique_fingerprints_per_email_per_10_minutes', 3);

        if ($differentDevices >= $threshold) {
            return new BlockingDecision(
                blocked: false,
                reason: __('auth.suspicious_activity_detected'),
                strategy: 'suspicious_activity',
            );
        }

        return null;
    }

    public function getPriority(): int
    {
        return 5;
    }
}
