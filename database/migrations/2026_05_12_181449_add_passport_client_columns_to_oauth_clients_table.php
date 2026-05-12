<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('oauth_clients', 'personal_access_client')) {
            Schema::table('oauth_clients', fn (Blueprint $table) => $table->boolean('personal_access_client')->default(false));
        }
        if (!Schema::hasColumn('oauth_clients', 'password_client')) {
            Schema::table('oauth_clients', fn (Blueprint $table) => $table->boolean('password_client')->default(false));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('oauth_clients', 'personal_access_client')) {
            Schema::table('oauth_clients', fn (Blueprint $table) => $table->dropColumn('personal_access_client'));
        }
        if (Schema::hasColumn('oauth_clients', 'password_client')) {
            Schema::table('oauth_clients', fn (Blueprint $table) => $table->dropColumn('password_client'));
        }
    }
};
