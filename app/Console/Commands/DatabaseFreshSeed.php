<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('db:fresh-seed')]
#[Description('Drop all tables, re-run all migrations, and seed the database')]
class DatabaseFreshSeed extends Command
{
    public function handle()
    {
        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);
    }
}
