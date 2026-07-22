<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->bigInteger('anilist_id')->unique()->nullable()->after('mal_id');
            $table->json('genres')->nullable()->after('rank');
            $table->json('authors')->nullable()->after('genres');
            $table->json('themes')->nullable()->after('authors');
            $table->json('demographics')->nullable()->after('themes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn(['anilist_id', 'genres', 'authors', 'themes', 'demographics']);
        });
    }
};
