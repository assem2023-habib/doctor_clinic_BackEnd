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

        if (Schema::hasColumn('medical_records', 'appointment_id')) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->dropForeign(['appointment_id']);
                $table->dropColumn('appointment_id');
            });
        }

        if (Schema::hasColumn('prescriptions', 'doctor_id')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->dropColumn('doctor_id');
            });
        }

        if (Schema::hasColumn('prescriptions', 'patient_id')) {
            Schema::table('prescriptions', function (Blueprint $table) {
                $table->dropForeign(['patient_id']);
                $table->dropColumn('patient_id');
            });
        }
    }

    public function down(): void
    {
    }
};
