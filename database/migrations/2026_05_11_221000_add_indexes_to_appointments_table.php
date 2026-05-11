<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index('doctor_id');
            $table->index('patient_id');
            $table->index('appointment_date');
            $table->index('status');
            $table->index(['doctor_id', 'appointment_date'], 'idx_appointments_doctor_date');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['doctor_id']);
            $table->dropIndex(['patient_id']);
            $table->dropIndex(['appointment_date']);
            $table->dropIndex(['status']);
            $table->dropIndex('idx_appointments_doctor_date');
        });
    }
};
