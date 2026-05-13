<?php

namespace App\Http\Controllers\Api\V1\Image;

use App\Domains\Images\Actions\DeleteImageAction;
use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Domains\Images\Models\Image;
use App\Domains\Images\Requests\UploadImageRequest;
use App\Domains\Images\Resources\ImageResource;
use App\Domains\Shared\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

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

    public function destroy(Image $image): JsonResponse
    {
        $this->deleteImageAction->execute($image);

        return ApiResponse::noContent(__('Image deleted successfully'));
    }
}
