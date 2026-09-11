<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur cover per-volume dihapus total — konsep intinya app ini cuma buat ngetrack progress
 * baca & kepemilikan (fisik/digital), bukan galeri gambar per volume. Cover series (kolom
 * `series.cover_path`) TETAP ada, cuma level Volume yang dihapus. File fisik yang udah
 * ke-upload lewat fitur ini (manual/AniList/SerpApi) dibiarin nganggur di storage — nggak
 * dihapus otomatis di sini karena StorageSettingsService (S3/local) nggak reliable dipanggil
 * dari migration; kalau perlu bersih-bersih storage, lakuin manual terpisah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('volumes', function (Blueprint $table) {
            $table->dropColumn('cover_path');
        });
    }

    public function down(): void
    {
        Schema::table('volumes', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('volume_number');
        });
    }
};
