<?php

namespace App\Domains\Auth\Services;

use App\Domains\Auth\Contracts\BlockingStrategyInterface;
use App\Domains\Auth\DTOs\BlockingDecision;
use App\Domains\Auth\DTOs\LoginAttemptData;
use App\Domains\Auth\DTOs\LoginSecurityContext;
use App\Domains\Auth\Contracts\LoginAttemptRepositoryInterface;
use App\Domains\Auth\Contracts\DeviceFingerprintServiceInterface;
use App\Domains\Auth\Events\LoginFromNewDevice;
use App\Domains\Auth\Events\SuspiciousLoginAttempts;
use Illuminate\Support\Facades\Event;

class LoginSecurityManager
{
    private array $strategies = [];

    public function __construct(
        private readonly LoginAttemptRepositoryInterface $repository,
        private readonly DeviceFingerprintServiceInterface $fingerprintService,
    ) {}

    public function addStrategy(BlockingStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    public function checkBlocked(LoginSecurityContext $context): ?BlockingDecision
    {
        $strategies = $this->strategies;
        usort($strategies, fn(BlockingStrategyInterface $a, BlockingStrategyInterface $b) => $a->getPriority() <=> $b->getPriority());

        foreach ($strategies as $strategy) {
            $decision = $strategy->check($context);
            if ($decision !== null && $decision->blocked) {
                return $decision;
            }
            if ($decision !== null && !$decision->blocked && $decision->reason === __('auth.suspicious_activity_detected')) {
                $this->handleSuspiciousActivity($context);
            }
        }

        return null;
    }

    public function recordAttempt(LoginAttemptData $data): void
    {
        $this->repository->recordAttempt($data);
    }

    public function handleSuccess(LoginSecurityContext $context): void
    {
        $this->repository->recordAttempt(new LoginAttemptData(
            email: $context->email,
            ip: $context->ip,
            deviceFingerprint: $context->deviceFingerprint,
            userAgent: $context->userAgent,
            success: true,
        ));

        if ($context->deviceFingerprint !== null) {
            $user = \App\Models\User::where('email', $context->email)->first();
            if ($user !== null) {
                $isKnown = $this->fingerprintService->isKnownDevice($user->id, $context->deviceFingerprint);

                if (!$isKnown) {
                    $this->fingerprintService->registerDevice(
                        $user->id,
                        $context->deviceFingerprint,
                        $context->ip,
                        $context->userAgent,
                        null
                    );

                    Event::dispatch(new LoginFromNewDevice(
                        user: $user,
                        ip: $context->ip,
                        deviceFingerprint: $context->deviceFingerprint,
                        userAgent: $context->userAgent,
                    ));
                } else {
                    $this->fingerprintService->registerDevice(
                        $user->id,
                        $context->deviceFingerprint,
                        $context->ip,
                        $context->userAgent,
                        null
                    );
                }
            }
        }
    }

    public function handleFailure(LoginSecurityContext $context): void
    {
        $this->repository->recordAttempt(new LoginAttemptData(
            email: $context->email,
            ip: $context->ip,
            deviceFingerprint: $context->deviceFingerprint,
            userAgent: $context->userAgent,
            success: false,
            failureReason: 'Invalid credentials',
        ));

        $recentFailures = $this->repository->countRecentFailuresByEmail($context->email, 60);
        $differentDevices = $this->repository->countRecentFailuresByEmailFromDifferentFingerprints($context->email, 10);

        $maxFailures = config('login-security.limits.max_failures_per_email_per_hour', 5);
        $maxDevices = config('login-security.limits.max_unique_fingerprints_per_email_per_10_minutes', 3);

        if ($recentFailures >= $maxFailures || $differentDevices >= $maxDevices) {
            $user = \App\Models\User::where('email', $context->email)->first();
            if ($user !== null) {
                Event::dispatch(new SuspiciousLoginAttempts(
                    user: $user,
                    ip: $context->ip,
                    deviceFingerprint: $context->deviceFingerprint,
                    failedAttempts: $recentFailures,
                    differentDevices: $differentDevices,
                ));
            }
        }
    }

    private function handleSuspiciousActivity(LoginSecurityContext $context): void
    {
        $user = \App\Models\User::where('email', $context->email)->first();
        if ($user !== null) {
            $recentFailures = $this->repository->countRecentFailuresByEmail($context->email, 60);
            $differentDevices = $this->repository->countRecentFailuresByEmailFromDifferentFingerprints($context->email, 10);

            Event::dispatch(new SuspiciousLoginAttempts(
                user: $user,
                ip: $context->ip,
                deviceFingerprint: $context->deviceFingerprint,
                failedAttempts: $recentFailures,
                differentDevices: $differentDevices,
            ));
        }
    }
}
