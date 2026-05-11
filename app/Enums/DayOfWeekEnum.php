<?php

namespace App\Enums;

enum DayOfWeekEnum: string
{
    case Sunday = 'sunday';
    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';
    case Saturday = 'saturday';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
