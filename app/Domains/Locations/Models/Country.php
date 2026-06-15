<?php

namespace App\Domains\Locations\Models;

use App\Domains\Images\Models\Image;
use App\Domains\Locations\Models\City;
use App\Domains\Shared\Traits\ClearsCache;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Country extends Model
{
    use HasUuidV7, ClearsCache;

    public function cacheVersionsToIncrement(): array
    {
        return ['countries:cache_version'];
    }

    protected $fillable = ['name', 'code', 'flag'];

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
