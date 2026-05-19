<?php

namespace App\Domains\Auth\Strategies;

use App\Domains\Auth\Contracts\BlockingStrategyInterface;
use App\Domains\Auth\Contracts\LoginAttemptRepositoryInterface;
use App\Domains\Auth\DTOs\BlockingDecision;
use App\Domains\Auth\DTOs\LoginSecurityContext;

class IpBlockingStrategy implements BlockingStrategyInterface
{
    public function __construct(
        private readonly LoginAttemptRepositoryInterface $repository,
    ) {}

    public function check(LoginSecurityContext $context): ?BlockingDecision
    {
        if ($this->repository->isIpBlocked($context->ip)) {
            return new BlockingDecision(
                blocked: true,
                reason: __('auth.ip_blocked'),
                retryAfterSeconds: 1800,
                strategy: 'ip',
            );
        }

        $recentFailures = $this->repository->countRecentFailuresByIp($context->ip, 15);

        $maxFailures = config('login-security.limits.max_failures_per_ip_per_15_minutes', 10);

        if ($recentFailures >= $maxFailures) {
            $duration = config('login-security.block_durations.ip_temporary', 30);
            $this->repository->blockIp(
                $context->ip,
                $duration,
                "تجاوز $recentFailures محاولة فاشلة من IP واحد في 15 دقيقة"
            );

            return new BlockingDecision(
                blocked: true,
                reason: __('auth.ip_temporarily_blocked'),
                retryAfterSeconds: $duration * 60,
                strategy: 'ip',
            );
        }

        return null;
    }

    public function getPriority(): int
    {
        return config('login-security.strategies.ip.priority', 20);
    }
}
