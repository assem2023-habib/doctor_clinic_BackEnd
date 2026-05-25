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

        $indexExists = collect(DB::select('SHOW INDEX FROM medical_records'))
            ->pluck('Key_name')
            ->contains('medical_records_patient_id_doctor_id_unique');

        if ($indexExists) {
            Schema::table('medical_records', function (Blueprint $table) {
                $table->dropUnique(['patient_id', 'doctor_id']);
            });
        }
    }

    public function down(): void
    {
    }
};
