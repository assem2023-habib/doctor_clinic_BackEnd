<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_patient', function (Blueprint $table) {
            $table->string('supervision_status', 20)->default('active')->after('notes');
            $table->timestamp('supervision_start')->nullable()->after('supervision_status');
            $table->timestamp('supervision_end')->nullable()->after('supervision_start');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_patient', function (Blueprint $table) {
            $table->dropColumn(['supervision_status', 'supervision_start', 'supervision_end']);
        });
    }
};
