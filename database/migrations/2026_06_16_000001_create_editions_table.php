<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->comment('Contoh: Bahasa Indonesia — Elex Media');
            $table->string('language', 10)->comment('Kode ISO 639-1: id, en, ja, fr, dsb.');
            $table->string('publisher')->nullable()->comment('Contoh: Elex Media, Viz Media, Shueisha');
            $table->timestamps();

            $table->index('language');
        });

        // Add FK from collections → editions (MySQL only; SQLite skips gracefully)
        if (DB::getDriverName() === 'mysql') {
            Schema::table('collections', function (Blueprint $table) {
                $table->foreign('edition_id')
                      ->references('id')->on('editions')
                      ->nullOnDelete();
            });
        }

        // Seed common editions
        $now = now();
        DB::table('editions')->insert([
            ['id' => \Illuminate\Support\Str::uuid(), 'name' => 'Bahasa Indonesia — Elex Media', 'language' => 'id', 'publisher' => 'Elex Media', 'created_at' => $now, 'updated_at' => $now],
            ['id' => \Illuminate\Support\Str::uuid(), 'name' => 'Bahasa Indonesia — M&C', 'language' => 'id', 'publisher' => 'M&C', 'created_at' => $now, 'updated_at' => $now],
            ['id' => \Illuminate\Support\Str::uuid(), 'name' => 'Bahasa Indonesia — Gramedia', 'language' => 'id', 'publisher' => 'Gramedia', 'created_at' => $now, 'updated_at' => $now],
            ['id' => \Illuminate\Support\Str::uuid(), 'name' => 'English — Viz Media', 'language' => 'en', 'publisher' => 'Viz Media', 'created_at' => $now, 'updated_at' => $now],
            ['id' => \Illuminate\Support\Str::uuid(), 'name' => 'English — Kodansha USA', 'language' => 'en', 'publisher' => 'Kodansha USA', 'created_at' => $now, 'updated_at' => $now],
            ['id' => \Illuminate\Support\Str::uuid(), 'name' => '日本語 (Original)', 'language' => 'ja', 'publisher' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('collections', function (Blueprint $table) {
                $table->dropForeign(['edition_id']);
            });
        }
        Schema::dropIfExists('editions');
    }
};
