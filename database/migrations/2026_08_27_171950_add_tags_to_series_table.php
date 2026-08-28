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
            // `tags`: array datar nama tag AniList (mis. "Isekai", "Anti-Hero") — dipakai buat
            // filtering (whereJsonContains), pola sama persis dengan kolom `genres` yang udah ada.
            $table->json('tags')->nullable()->after('demographics');
            // `tag_categories`: map nama tag -> kategori AniList (mis. "Isekai" -> "Theme-Reincarnation")
            // — cuma dipakai buat ngelompokin tag jadi tree di UI filter, TIDAK pernah di-query
            // langsung di WHERE clause (jadi nggak perlu driver-aware kayak `tags`/`genres`).
            $table->json('tag_categories')->nullable()->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn(['tags', 'tag_categories']);
        });
    }
};
