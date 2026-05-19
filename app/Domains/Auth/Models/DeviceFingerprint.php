<?php

namespace App\Domains\Auth\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceFingerprint extends Model
{
    protected $fillable = [
        'fingerprint_hash',
        'user_agent',
        'fingerprint_data',
        'ip_first_seen',
        'ip_last_seen',
        'first_seen_at',
        'last_seen_at',
        'blocked_until',
        'block_reason',
    ];

    protected function casts(): array
    {
        return [
            'fingerprint_data' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'blocked_until' => 'datetime',
        ];
    }

    public function scopeBlocked($query)
    {
        return $query->where('blocked_until', '>', now());
    }
}
