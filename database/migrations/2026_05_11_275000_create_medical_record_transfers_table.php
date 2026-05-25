<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_record_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('medical_record_id')->constrained('medical_records')->cascadeOnDelete();
            $table->uuid('from_doctor_id')->nullable()->index();
            $table->foreignUuid('to_doctor_id')->constrained('doctors');
            $table->foreignUuid('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignUuid('transferred_by')->constrained('users');
            $table->string('initiated_by_role');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_record_transfers');
    }
};
