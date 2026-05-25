<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropUnique(['patient_id', 'doctor_id']);
        });
    }

    public function down(): void
    {
    }
};
