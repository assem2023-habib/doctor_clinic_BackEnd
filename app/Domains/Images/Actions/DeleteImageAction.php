<?php

namespace App\Domains\Images\Actions;

use App\Domains\Images\Models\Image;
use Illuminate\Support\Facades\Storage;

class DeleteImageAction
{
    public function execute(Image $image): void
    {
        Storage::disk('public')->delete($image->url);
        $image->delete();
    }
}
