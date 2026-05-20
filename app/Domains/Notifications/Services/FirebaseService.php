<?php

namespace App\Domains\Notifications\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Exception\FirebaseException;

class FirebaseService
{
    private ?Factory $factory = null;

    public function __construct()
    {
        $credentials = config('notification.channels.firebase.credentials');

        if (empty($credentials) || !file_exists($credentials)) {
            Log::warning('Firebase credentials file not found', ['path' => $credentials]);
            return;
        }

        try {
            $this->factory = (new Factory)->withServiceAccount($credentials);
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase', ['message' => $e->getMessage()]);
        }
    }

    public function isAvailable(): bool
    {
        return $this->factory !== null;
    }

    public function messaging(): ?Messaging
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            return $this->factory->createMessaging();
        } catch (FirebaseException $e) {
            Log::error('Failed to create Firebase Messaging', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
