<?php

namespace App\Enums;

enum RatingTypeEnum: string
{
    case User = 'user';
    case Service = 'service';
    case Center = 'center';
    case AppointmentSystem = 'appointment_system';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
