<?php

namespace App\Domains\Auth\Providers;

use App\Domains\Auth\Contracts\BlockingStrategyInterface;
use App\Domains\Auth\Contracts\DeviceFingerprintServiceInterface;
use App\Domains\Auth\Contracts\LoginAttemptRepositoryInterface;
use App\Domains\Auth\Events\LoginFromNewDevice;
use App\Domains\Auth\Events\SuspiciousLoginAttempts;
use App\Domains\Auth\Listeners\NotifySuspiciousActivity;
use App\Domains\Auth\Listeners\SendNewDeviceNotification;
use App\Domains\Auth\Repositories\EloquentLoginAttemptRepository;
use App\Domains\Auth\Services\DeviceFingerprintService;
use App\Domains\Auth\Services\LoginSecurityManager;
use App\Domains\Auth\Strategies\FingerprintBlockingStrategy;
use App\Domains\Auth\Strategies\IpBlockingStrategy;
use App\Domains\Auth\Strategies\SuspiciousActivityStrategy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class LoginSecurityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LoginAttemptRepositoryInterface::class, EloquentLoginAttemptRepository::class);
        $this->app->bind(DeviceFingerprintServiceInterface::class, DeviceFingerprintService::class);

        $this->app->singleton(LoginSecurityManager::class, function ($app) {
            $manager = new LoginSecurityManager(
                repository: $app->make(LoginAttemptRepositoryInterface::class),
                fingerprintService: $app->make(DeviceFingerprintServiceInterface::class),
            );

            if (config('login-security.strategies.fingerprint.enabled', true)) {
                $manager->addStrategy(new FingerprintBlockingStrategy(
                    $app->make(LoginAttemptRepositoryInterface::class),
                ));
            }

            if (config('login-security.strategies.ip.enabled', true)) {
                $manager->addStrategy(new IpBlockingStrategy(
                    $app->make(LoginAttemptRepositoryInterface::class),
                ));
            }

            $manager->addStrategy(new SuspiciousActivityStrategy(
                $app->make(LoginAttemptRepositoryInterface::class),
            ));

            return $manager;
        });
    }

    public function boot(): void
    {
        Event::listen(
            LoginFromNewDevice::class,
            SendNewDeviceNotification::class,
        );

        Event::listen(
            SuspiciousLoginAttempts::class,
            NotifySuspiciousActivity::class,
        );
    }
}
