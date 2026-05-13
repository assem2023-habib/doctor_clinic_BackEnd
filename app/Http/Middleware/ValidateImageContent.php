<?php

namespace App\Http\Middleware;

use App\Domains\Shared\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Intervention\Image\Laravel\Facades\Image as InterventionImage;

class ValidateImageContent
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $request->hasFile('file')) {
            return $next($request);
        }

        $file = $request->file('file');

        try {
            InterventionImage::decodePath($file->getRealPath());
        } catch (\Throwable) {
            return ApiResponse::validationError(
                __('The file must be a valid image.'),
                ['file' => [__('The uploaded file is not a valid image or contains malicious content.')]],
            );
        }

        return $next($request);
    }
}
