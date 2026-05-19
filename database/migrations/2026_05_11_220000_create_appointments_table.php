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
            $table->uuid('doctor_id')->nullable();
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->date('appointment_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('status', AppointmentStatusEnum::values())->default('pending');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by', 500);
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
