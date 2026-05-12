<?php

use App\Enums\RatingTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rater_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', RatingTypeEnum::values());
            $table->uuid('rateable_id')->nullable();
            $table->string('rateable_type')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['rateable_type', 'rateable_id']);
            $table->index('rater_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
