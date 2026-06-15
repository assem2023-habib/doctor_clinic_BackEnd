<?php

namespace App\Domains\Ratings\Models;

use App\Domains\Shared\Traits\ClearsCache;
use App\Enums\RatingTypeEnum;
use App\Models\User;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Rating extends Model
{
    use HasUuidV7, ClearsCache;

    protected $fillable = [
        'rater_id',
        'type',
        'rateable_id',
        'rateable_type',
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'type' => RatingTypeEnum::class,
            'rating' => 'integer',
        ];
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function rateable(): MorphTo
    {
        return $this->morphTo();
    }

    public function cacheVersionsToIncrement(): array
    {
        $versions = ['ratings:cache_version'];

        if ($this->type === RatingTypeEnum::User) {
            $versions[] = 'doctors:cache_version';
        }

        return $versions;
    }
}
