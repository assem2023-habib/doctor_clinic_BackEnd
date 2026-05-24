<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LocationSeeder::class);
        $this->call(RbacSeeder::class);
        $this->call(SpecializationSeeder::class);
        $this->call(UserSeeder::class);
    }
}
