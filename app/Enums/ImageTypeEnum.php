<?php

namespace App\Enums;

enum ImageTypeEnum: string
{
    case User = 'user';
    case Country = 'country';
    case Specialization = 'specialization';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function maxSize(): int
    {
        return config("images.max_size.{$this->value}", 2048);
    }
}
