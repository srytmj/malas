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
            $table->string('isbn', 20)->nullable()->comment('ISBN-13');
            $table->string('cover_path')->nullable()->comment('Cover spesifik volume di R2');
            $table->date('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['series_id', 'volume_number']);
            $table->index('series_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volumes');
    }
};
