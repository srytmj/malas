<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedInteger('mal_id')->unique();
            $table->string('title_romaji');
            $table->string('title_english')->nullable();
            $table->string('title_japanese')->nullable();
            $table->text('synopsis')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->enum('status', ['publishing','finished','on_hiatus','discontinued','not_yet_published'])->nullable();
            $table->unsignedSmallInteger('total_volumes')->nullable();
            $table->date('published_from')->nullable();
            $table->date('published_to')->nullable();
            $table->decimal('score', 4, 2)->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->json('authors')->nullable();
            $table->json('genres')->nullable();
            $table->json('demographics')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('deleted_by')->nullable();
            $table->text('deletion_reason')->nullable();
        });
    }

    public function down(): void { Schema::dropIfExists('series'); }
};
