<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('mal_id')->nullable()->comment('MyAnimeList manga ID');
            $table->string('title_romaji');
            $table->string('title_english')->nullable();
            $table->string('title_japanese')->nullable();
            $table->text('synopsis')->nullable();
            $table->string('cover_path')->nullable()->comment('Path di Cloudflare R2');
            $table->enum('status', [
                'publishing',
                'finished',
                'on_hiatus',
                'discontinued',
                'not_yet_published',
            ])->default('publishing');
            $table->date('published_from')->nullable();
            $table->date('published_to')->nullable();
            $table->unsignedInteger('total_volumes')->nullable();
            $table->decimal('score', 4, 2)->nullable()->comment('0.00 – 10.00');
            $table->unsignedInteger('rank')->nullable();
            $table->softDeletes();
            $table->text('deleted_reason')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            // Unique on mal_id — both MySQL and SQLite allow multiple NULLs in a UNIQUE index
            $table->unique('mal_id');
            $table->index('status');
        });

        // Full-text search on title (MySQL only — SQLite skips this gracefully)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE series ADD FULLTEXT idx_series_title (title_romaji)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
