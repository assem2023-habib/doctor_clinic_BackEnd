<?php

namespace App\Enums;

enum RoleEnum: string
{
    case Admin = 'admin';
    case Doctor = 'doctor';
    case Patient = 'patient';
    case Receptionist = 'receptionist';
}
