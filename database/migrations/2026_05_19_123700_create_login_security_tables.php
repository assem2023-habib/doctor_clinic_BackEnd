<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->index();
            $table->string('ip', 45)->index();
            $table->string('device_fingerprint', 64)->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->boolean('success');
            $table->string('failure_reason')->nullable();
            $table->timestamp('attempted_at');

            $table->index(['ip', 'attempted_at']);
            $table->index(['device_fingerprint', 'attempted_at']);
            $table->index(['email', 'attempted_at']);
        });

        Schema::create('device_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint_hash', 64)->unique();
            $table->string('user_agent', 500)->nullable();
            $table->json('fingerprint_data')->nullable();
            $table->ipAddress('ip_first_seen')->nullable();
            $table->ipAddress('ip_last_seen')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('blocked_until')->nullable()->index();
            $table->string('block_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('known_user_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('device_fingerprint', 64);
            $table->string('device_name', 255)->nullable();
            $table->ipAddress('ip_first_seen')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('trusted_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'device_fingerprint']);
            $table->index(['user_id', 'trusted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('known_user_devices');
        Schema::dropIfExists('device_fingerprints');
        Schema::dropIfExists('login_attempts');
    }
};
