<?php

namespace App\Domains\Notifications\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Database;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Factory;

class FirebaseRtdbService
{
    private ?Database $database = null;

    public function __construct()
    {
        $credentials = config('notification.channels.firebase.credentials');
        $rtdbUrl = config('notification.channels.firebase.rtdb_url');

        if (empty($credentials) || !file_exists($credentials)) {
            Log::warning('Firebase credentials file not found for RTDB', ['path' => $credentials]);
            return;
        }

        if (empty($rtdbUrl)) {
            Log::warning('Firebase RTDB URL not configured');
            return;
        }

        try {
            $this->database = (new Factory)
                ->withServiceAccount($credentials)
                ->withDatabaseUri($rtdbUrl)
                ->createDatabase();
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase RTDB', ['message' => $e->getMessage()]);
        }
    }

    public function isAvailable(): bool
    {
        return $this->database !== null;
    }

    public function getDatabase(): ?Database
    {
        return $this->database;
    }

    public function setValue(string $path, mixed $value): void
    {
        if (!$this->isAvailable()) {
            return;
        }

        try {
            $this->database->getReference($path)->set($value);
        } catch (FirebaseException $e) {
            Log::error('Failed to write to Firebase RTDB', ['path' => $path, 'message' => $e->getMessage()]);
        }
    }

    public function removeValue(string $path): void
    {
        if (!$this->isAvailable()) {
            return;
        }

        try {
            $this->database->getReference($path)->remove();
        } catch (FirebaseException $e) {
            Log::error('Failed to remove from Firebase RTDB', ['path' => $path, 'message' => $e->getMessage()]);
        }
    }

    public function getValue(string $path): mixed
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            return $this->database->getReference($path)->getValue();
        } catch (FirebaseException $e) {
            Log::error('Failed to read from Firebase RTDB', ['path' => $path, 'message' => $e->getMessage()]);
            return null;
        }
    }
}
