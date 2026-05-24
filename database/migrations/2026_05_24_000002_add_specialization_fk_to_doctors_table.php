<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->foreignUuid('specialization_id')->after('user_id')->constrained('specializations');

            $table->dropColumn('specialization');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('specialization', 100)->nullable()->after('user_id');

            $table->dropConstrainedForeignId('specialization_id');
        });
    }
};
