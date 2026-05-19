<?php

namespace App\Domains\Auth\Strategies;

use App\Domains\Auth\Contracts\BlockingStrategyInterface;
use App\Domains\Auth\Contracts\LoginAttemptRepositoryInterface;
use App\Domains\Auth\DTOs\BlockingDecision;
use App\Domains\Auth\DTOs\LoginSecurityContext;

class FingerprintBlockingStrategy implements BlockingStrategyInterface
{
    public function __construct(
        private readonly LoginAttemptRepositoryInterface $repository,
    ) {}

    public function check(LoginSecurityContext $context): ?BlockingDecision
    {
        if ($context->deviceFingerprint === null) {
            return null;
        }

        if ($this->repository->isDeviceBlocked($context->deviceFingerprint)) {
            return new BlockingDecision(
                blocked: true,
                reason: __('auth.device_blocked'),
                retryAfterSeconds: 3600,
                strategy: 'fingerprint',
            );
        }

        $recentFailures = $this->repository->countRecentFailuresByFingerprint(
            $context->deviceFingerprint,
            30
        );

        $maxFailures = config('login-security.limits.max_failures_per_fingerprint_per_30_minutes', 5);

        if ($recentFailures >= $maxFailures) {
            $duration = config('login-security.block_durations.fingerprint_temporary', 60);
            $this->repository->blockDevice(
                $context->deviceFingerprint,
                $duration,
                "تجاوز $recentFailures محاولة فاشلة في 30 دقيقة"
            );

            return new BlockingDecision(
                blocked: true,
                reason: __('auth.device_temporarily_blocked'),
                retryAfterSeconds: $duration * 60,
                strategy: 'fingerprint',
            );
        }

        return null;
    }

    public function getPriority(): int
    {
        return config('login-security.strategies.fingerprint.priority', 10);
    }
}
