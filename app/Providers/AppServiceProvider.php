<?php

namespace App\Providers;

use App\Domains\Appointments\Repositories\AppointmentRepositoryInterface;
use App\Domains\Appointments\Repositories\EloquentAppointmentRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AppointmentRepositoryInterface::class,
            EloquentAppointmentRepository::class,
        );
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $limit = (int) env('API_RATE_LIMIT', 60);

            return Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });
    }
}
