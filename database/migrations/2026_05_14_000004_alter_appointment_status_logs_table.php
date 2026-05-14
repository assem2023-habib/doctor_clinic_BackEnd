<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_status_logs', function (Blueprint $table) {
            $table->dropForeign(['changed_by']);
            $table->string('changed_by', 500)->change();
        });
    }

    public function down(): void
    {
        Schema::table('appointment_status_logs', function (Blueprint $table) {
            $table->foreignUuid('changed_by')->change();
            $table->foreign('changed_by')->references('id')->on('users');
        });
    }
};
