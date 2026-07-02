<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volumes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('series_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('volume_number');
            $table->string('cover_path')->nullable();
            $table->enum('type', ['regular', 'digital', 'bind_up'])->default('regular');
            $table->string('digital_source')->nullable();
            $table->string('isbn')->nullable();
            $table->date('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['series_id', 'volume_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volumes');
    }
};
