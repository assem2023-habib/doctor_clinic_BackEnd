<?php

namespace App\Domains\RBAC\Models;

use App\Domains\Shared\Traits\ClearsCache;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasUuidV7, ClearsCache;

    protected $fillable = ['name', 'slug', 'description', 'group', 'guard_name'];

    public function cacheVersionsToIncrement(): array
    {
        return ['permissions:cache_version'];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
