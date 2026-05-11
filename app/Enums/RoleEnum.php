<?php

namespace App\Enums;

enum RoleEnum: string
{
    case Admin = 'admin';
    case Doctor = 'doctor';
    case Patient = 'patient';
    case Receptionist = 'receptionist';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
