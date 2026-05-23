<?php

namespace App\Domains\Notifications\Providers;

use App\Domains\Notifications\Channels\DatabaseChannel;
use App\Domains\Notifications\Channels\FirebaseChannel;
use App\Domains\Notifications\Channels\LogChannel;
use App\Domains\Notifications\Channels\SocketIOChannel;
use App\Domains\Notifications\Channels\WebSocketChannel;
use App\Domains\Notifications\Services\FirebaseRtdbService;
use App\Domains\Notifications\Services\FirebaseService;
use App\Domains\Notifications\Services\NotificationManager;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FirebaseService::class, function () {
            return new FirebaseService();
        });

        $this->app->singleton(FirebaseRtdbService::class, function () {
            return new FirebaseRtdbService();
        });

        $this->app->singleton(NotificationManager::class, function () {
            $manager = new NotificationManager();

            $manager->addChannel('log', new LogChannel());
            $manager->addChannel('database', new DatabaseChannel());
            $manager->addChannel('firebase', new FirebaseChannel($this->app->make(FirebaseService::class)));
            $manager->addChannel('websocket', new WebSocketChannel());
            $manager->addChannel('socketio', new SocketIOChannel());

            return $manager;
        });
    }
}
