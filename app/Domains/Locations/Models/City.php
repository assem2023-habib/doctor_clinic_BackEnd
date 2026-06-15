<?php

namespace App\Domains\Locations\Models;

use App\Domains\Locations\Models\Country;
use App\Domains\Shared\Traits\ClearsCache;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasUuidV7, ClearsCache;

    public function cacheVersionsToIncrement(): array
    {
        return ['cities:cache_version'];
    }

    protected $fillable = ['name', 'country_id'];

    protected function casts(): array
    {
        return [
            'name' => 'array',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
