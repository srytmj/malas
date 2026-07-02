<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Loans referencing volumes via volume_id — truncate before schema change
        DB::table('loans')->truncate();
        DB::table('collection_volumes')->truncate();

        Schema::dropIfExists('collection_volumes');
        Schema::create('collection_volumes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('volume_number');
            $table->enum('format', ['physical', 'ebook', 'online', 'webtoon'])->default('physical');
            $table->timestamps();

            $table->unique(['collection_id', 'volume_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_volumes');
        Schema::create('collection_volumes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('volume_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_owned')->default(true);
            $table->timestamps();

            $table->unique(['collection_id', 'volume_id']);
        });
    }
};
