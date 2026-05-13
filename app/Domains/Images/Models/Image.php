<?php

namespace App\Domains\Images\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasUuidV7;

    protected $fillable = ['url', 'type', 'imageable_id', 'imageable_type'];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return url('/api/v1/images/' . $this->id);
    }

    public function isOwnedBy(\App\Models\User $user): bool
    {
        if ($this->imageable_type !== 'user') {
            return false;
        }

        return $this->imageable_id === $user->id;
    }
}
