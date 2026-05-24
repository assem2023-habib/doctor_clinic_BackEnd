<?php

namespace Database\Seeders;

use App\Domains\Doctors\Models\Specialization;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    public function run(): void
    {
        Specialization::seedFromEnum();
    }
}
