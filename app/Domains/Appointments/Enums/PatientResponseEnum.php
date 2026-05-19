<?php

namespace App\Domains\Appointments\Enums;

enum PatientResponseEnum: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
