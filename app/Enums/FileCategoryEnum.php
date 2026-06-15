<?php

namespace App\Enums;

enum FileCategoryEnum: string
{
    case Document = 'document';
    case LabResult = 'lab_result';
    case XRay = 'xray';
    case Prescription = 'prescription';
    case Report = 'report';
    case Other = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
