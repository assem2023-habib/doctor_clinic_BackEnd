<?php

use App\Enums\AppointmentStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', AppointmentStatusEnum::values())->default('pending');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->constrained('users');
            $table->timestamps();

            $table->index('doctor_id');
            $table->index('patient_id');
            $table->index('appointment_date');
            $table->index('status');
            $table->index(['doctor_id', 'appointment_date'], 'idx_appointments_doctor_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
