<?php

namespace App\Domains\Doctors\Actions;

use App\Domains\Doctors\DTOs\SpecializationData;
use App\Domains\Doctors\Models\Specialization;
use App\Domains\Images\Actions\UploadImageAction;
use App\Domains\Images\DTOs\UploadImageData;
use App\Enums\ImageTypeEnum;
use Illuminate\Http\UploadedFile;

class UpdateSpecializationAction
{
    public function __construct(
        private readonly UploadImageAction $uploadImageAction,
    ) {}

    public function execute(Specialization $specialization, SpecializationData $data, ?UploadedFile $file = null): Specialization
    {
        $specialization->update($data->toArray());

        if ($file) {
            $this->uploadImageAction->execute(UploadImageData::fromArray([
                'file' => $file,
                'type' => ImageTypeEnum::Specialization,
                'imageable_id' => $specialization->id,
            ]));
        }

        return $specialization->fresh();
    }
}
