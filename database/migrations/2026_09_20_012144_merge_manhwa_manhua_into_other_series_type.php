<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manhwa/manhua digabung jadi tipe "other" — fokus app ini cuma tracking koleksi
     * fisik/digital (bookwalker/amazon), bukan bedain asal negara komik. Kolom `type`
     * di-downgrade dari enum() jadi string biasa karena ALTER enum beda caranya per driver
     * (MySQL MODIFY vs Postgres CHECK constraint vs SQLite rebuild table) — validasi nilai
     * yang valid tetap dijaga di StoreSeriesRequest/UpdateSeriesRequest (Rule::in), bukan di DB.
     */
    public function up(): void
    {
        DB::table('series')->whereIn('type', ['manhwa', 'manhua'])->update(['type' => 'other']);

        Schema::table('series', function (Blueprint $table) {
            $table->string('type', 20)->default('manga')->change();
        });
    }

    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->enum('type', ['manga', 'manhwa', 'manhua', 'novel', 'one_shot', 'doujinshi'])->default('manga')->change();
        });
    }
};
