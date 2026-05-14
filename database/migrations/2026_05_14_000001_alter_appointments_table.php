<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['doctor_id']);
            $table->dropForeign(['created_by']);

            $table->uuid('doctor_id')->nullable()->change();
            $table->string('created_by', 500)->change();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->uuid('doctor_id')->nullable(false)->change();
            $table->foreignUuid('created_by')->change();

            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });
    }
};
