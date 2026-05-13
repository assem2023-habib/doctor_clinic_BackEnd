<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasUuidV7;

    protected function casts(): array
    {
        return [
            'name' => 'array',
        ];
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }
}
