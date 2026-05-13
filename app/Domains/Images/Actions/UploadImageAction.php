<?php

namespace App\Domains\Images\Actions;

use App\Domains\Images\DTOs\UploadImageData;
use App\Domains\Images\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;

class UploadImageAction
{
    public function execute(UploadImageData $data): Image
    {
        $existing = Image::where('imageable_type', $data->type->value)
            ->where('imageable_id', $data->imageableId)
            ->first();

        if ($existing) {
            Storage::disk('public')->delete($existing->url);
            $existing->delete();
        }

        $filename = Uuid::uuid7()->toString() . '.' . $data->file->extension();
        $relativePath = $data->file->storeAs(
            'uploads/' . $data->type->value . '/' . $data->imageableId,
            $filename,
            'public'
        );

        return Image::create([
            'url' => $relativePath,
            'imageable_type' => $data->type->value,
            'imageable_id' => $data->imageableId,
        ]);
    }
}
