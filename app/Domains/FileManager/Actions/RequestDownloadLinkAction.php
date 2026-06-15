<?php

namespace App\Domains\FileManager\Actions;

use App\Domains\FileManager\Models\File;
use App\Domains\FileManager\Services\FileAccessService;
use App\Models\User;
use Illuminate\Support\Facades\URL;

class RequestDownloadLinkAction
{
    public function __construct(
        private readonly FileAccessService $accessService,
    ) {}

    public function execute(File $file, User $user): array
    {
        if (! $this->accessService->canAccess($user, $file)) {
            throw new \RuntimeException('You do not have access to this file');
        }

        $expiresAt = now()->endOfDay();

        $url = URL::temporarySignedRoute(
            'files.download',
            $expiresAt,
            ['file' => $file->id, 'user' => $user->id],
        );

        return [
            'url' => $url,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
