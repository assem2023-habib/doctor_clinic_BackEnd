<?php

use App\Enums\FileUploadStatusEnum;
use App\Enums\StorageDiskEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('medical_record_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('path')->nullable();
            $table->string('disk', 50)->default(StorageDiskEnum::Local->value);
            $table->string('checksum', 64)->nullable();
            $table->string('upload_status', 50)->default(FileUploadStatusEnum::Pending->value);
            $table->string('file_category', 50);
            $table->unsignedSmallInteger('total_chunks')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('upload_status');
            $table->index(['medical_record_id', 'file_category']);
        });

        Schema::create('file_downloads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('downloaded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_downloads');
        Schema::dropIfExists('files');
    }
};
