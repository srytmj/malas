<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            // Grouping/label bebas per user (mis. "Rak Kamar", "Rak Kantor") — sengaja string bebas,
            // bukan tabel terpisah, biar nggak butuh CRUD grup sendiri. Filter di UI dedup dari
            // nilai yang sudah ada per user.
            $table->string('group_name', 100)->nullable()->after('condition');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });
    }
};
