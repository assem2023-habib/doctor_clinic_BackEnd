<?php

namespace App\Domains\Images\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasUuidV7;

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Storage::disk('public')->url($value);
    }
}
