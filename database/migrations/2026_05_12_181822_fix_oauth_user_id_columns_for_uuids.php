<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->modifyColumnType('oauth_auth_codes', 'user_id', 'VARCHAR(36)');
        $this->modifyColumnType('oauth_access_tokens', 'user_id', 'VARCHAR(36)');
        // oauth_refresh_tokens has no user_id column
        $this->modifyColumnType('oauth_device_codes', 'user_id', 'VARCHAR(36)');
    }

    public function down(): void
    {
        $this->modifyColumnType('oauth_auth_codes', 'user_id', 'BIGINT');
        $this->modifyColumnType('oauth_access_tokens', 'user_id', 'BIGINT');
        // oauth_refresh_tokens has no user_id column
        $this->modifyColumnType('oauth_device_codes', 'user_id', 'BIGINT');
    }

    private function modifyColumnType(string $table, string $column, string $type): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            $rawTable = DB::getTablePrefix() . $table;
            DB::statement("ALTER TABLE {$rawTable} MODIFY {$column} {$type} NULL");
        }
    }
};
