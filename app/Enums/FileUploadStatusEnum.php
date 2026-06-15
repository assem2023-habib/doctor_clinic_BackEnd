<?php

namespace App\Enums;

enum FileUploadStatusEnum: string
{
    case Pending = 'pending';
    case Uploading = 'uploading';
    case Completed = 'completed';
    case Failed = 'failed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
