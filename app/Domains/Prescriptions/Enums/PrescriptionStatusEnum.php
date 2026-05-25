<?php

namespace App\Domains\Prescriptions\Enums;

enum PrescriptionStatusEnum: string
{
    case Active = 'active';
    case Archived = 'archived';
    case Expired = 'expired';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
