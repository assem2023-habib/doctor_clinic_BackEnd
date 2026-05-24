<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_patient', function (Blueprint $table) {
            $table->uuid('doctor_id');
            $table->uuid('patient_id');
            $table->string('assigned_by', 500);
            $table->text('notes')->nullable();
            $table->string('supervision_status', 20)->default('active');
            $table->timestamp('supervision_start')->nullable();
            $table->timestamp('supervision_end')->nullable();
            $table->timestamps();

            $table->unique(['doctor_id', 'patient_id']);

            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_patient');
    }
};
