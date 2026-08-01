<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genre_funfacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->text('content')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->unsignedInteger('collections_count_at_generation')->nullable();
            $table->unsignedTinyInteger('manual_regenerate_count')->default(0);
            $table->timestamp('manual_regenerate_window_started_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genre_funfacts');
    }
};
