<?php

namespace App\Providers;

use App\Domains\Appointments\Repositories\AppointmentRepositoryInterface;
use App\Domains\Appointments\Repositories\EloquentAppointmentRepository;
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
        //
    }
}
