<?php

namespace App\Enums;

enum AppointmentStatusEnum: string
{
    case Pending = 'pending';
    case Requested = 'requested';
    case Set = 'set';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case InProgress = 'in_progress';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
