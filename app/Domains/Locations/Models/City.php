<?php

namespace App\Domains\Locations\Models;

use App\Domains\Locations\Models\Country;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasUuidV7;

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
