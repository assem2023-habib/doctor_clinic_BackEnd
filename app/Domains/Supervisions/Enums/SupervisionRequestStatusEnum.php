<?php

namespace App\Domains\Supervisions\Enums;

enum SupervisionRequestStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
