<?php

namespace App\Domains\Receptionists\Models;

use App\Domains\Shared\Traits\ClearsCache;
use App\Models\User;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receptionist extends Model
{
    use HasUuidV7, ClearsCache;

    public function cacheVersionsToIncrement(): array
    {
        return ['receptionists:cache_version'];
    }

    protected $fillable = ['user_id', 'shift_start', 'shift_end'];

    public function getRouteKeyName(): string
    {
        return 'user_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
