<?php

use App\Models\AiSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Puter.js dihapus dari aplikasi — provider 'puter' bukan pilihan valid lagi. Baris lama
        // yang masih ke-set 'puter' dinormalisasi ke 'gemini' (default baru), tanpa API key —
        // AiFunfactService sudah otomatis fallback ke teks statis kalau api_key kosong, jadi
        // perilakunya tetap sama (nggak nge-hit AI beneran) sampai admin isi API key baru.
        AiSetting::where('provider', 'puter')->update(['provider' => 'gemini']);
    }

    public function down(): void
    {
        // Nggak ada state sebelumnya yang perlu dikembalikan — fitur Puter sengaja dihapus.
    }
};
