<?php

namespace App\Enums;

enum StorageDiskEnum: string
{
    case Local = 'local';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
