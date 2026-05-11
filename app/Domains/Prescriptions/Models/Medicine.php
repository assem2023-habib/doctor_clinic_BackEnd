<?php

namespace App\Domains\Prescriptions\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'name',
        'description',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }
}
