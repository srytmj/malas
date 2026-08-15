<?php

use App\Models\CollectionGroup;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Grup yang dibuat sebelum migration slug (2026_08_14_110000) punya slug NULL — kolomnya
        // ditambah `nullable()` supaya migration nggak gagal di data lama, tapi lupa di-backfill.
        // Pola sama dengan backfill slug Series (lihat 2026_08_02_090000_add_slug_to_series_table.php).
        CollectionGroup::whereNull('slug')->with('user')->get()->each(function (CollectionGroup $group) {
            $group->slug = CollectionGroup::generateUniqueSlug($group->user, $group->name, $group->id);
            $group->saveQuietly();
        });
    }

    public function down(): void
    {
        // Nggak ada state sebelumnya yang perlu dikembalikan — slug NULL cuma bug data, bukan fitur.
    }
};
