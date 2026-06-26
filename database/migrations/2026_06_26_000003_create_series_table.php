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
            $table->bigInteger('mal_id')->unique()->nullable();
            $table->string('title_romaji');
            $table->string('title_english')->nullable();
            $table->string('title_japanese')->nullable();
            $table->text('synopsis')->nullable();
            $table->string('cover_path')->nullable();
            $table->enum('status', ['publishing', 'finished', 'on_hiatus', 'discontinued', 'not_yet_published'])->default('publishing');
            $table->enum('type', ['manga', 'manhwa', 'manhua', 'novel', 'one_shot', 'doujinshi'])->default('manga');
            $table->date('published_from')->nullable();
            $table->date('published_to')->nullable();
            $table->unsignedInteger('total_volumes')->nullable();
            $table->decimal('score', 4, 2)->nullable();
            $table->unsignedInteger('rank')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
