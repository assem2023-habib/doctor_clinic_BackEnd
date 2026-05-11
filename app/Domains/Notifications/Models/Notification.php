<?php

namespace App\Domains\Notifications\Models;

use App\Models\User;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Notification extends Model
{
    use HasUuidV7;

    protected function casts(): array
    {
        return [
            'body' => 'array',
        ];
    }

    protected $fillable = [
        'topic',
        'title',
        'body',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('read_at')
            ->withTimestamps();
    }
}
