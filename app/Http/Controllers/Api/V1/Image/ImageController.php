<?php

namespace App\Http\Controllers\Api\V1\Image;

use App\Domains\Images\Actions\DeleteImageAction;
use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Domains\Images\Models\Image;
use App\Domains\Images\Requests\UploadImageRequest;
use App\Domains\Images\Resources\ImageResource;
use App\Domains\Shared\Exceptions\ApiServiceException;
use App\Domains\Shared\Responses\ApiResponse;
use App\Enums\HttpStatusEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ImageController
{
    public function __construct(
        private readonly UploadImageAction $uploadImageAction,
        private readonly DeleteImageAction $deleteImageAction,
    ) {}

    public function store(UploadImageRequest $request): JsonResponse
    {
        $dto = UploadImageData::fromRequest($request->validated());
        $image = $this->uploadImageAction->execute($dto);

        return ApiResponse::created(
            new ImageResource($image),
            __('Image uploaded successfully')
        );
    }

    public function show(Image $image): \Illuminate\Http\Response
    {
        $user = auth()->user();

        if (!$user->hasRole('admin') && !$image->isOwnedBy($user)) {
            return ApiResponse::forbidden(__('You do not own this image'));
        }

        $path = $image->getRawOriginal('url');

        if (! Storage::disk('local')->exists($path)) {
            throw new ApiServiceException(
                errorCode: 'IMAGE_NOT_FOUND',
                message: __('Image not found'),
                status: HttpStatusEnum::NotFound,
            );
        }

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => Storage::disk('local')->mimeType($path),
        ]);
    }

    public function destroy(Image $image): JsonResponse
    {
        $this->deleteImageAction->execute($image);

        return ApiResponse::noContent(__('Image deleted successfully'));
    }
}
