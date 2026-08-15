<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MDList/MangaDex-style custom list — many-to-many, satu manga bisa masuk lebih dari satu
        // grup. Gantiin pendekatan lama (collections.group_name, satu string per koleksi) yang
        // ternyata salah semantik: user butuh grup sebagai objek pertama (nama, bisa diisi macam-
        // macam manga dari koleksinya), bukan sekadar label tunggal per koleksi.
        Schema::create('collection_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();
        });

        // Pivot murni — sengaja TIDAK pakai uuid('id') primary key kayak tabel lain. attach()/
        // syncWithoutDetaching() Eloquent insert baris pivot lewat query builder mentah (bukan lewat
        // model event), jadi HasUuids nggak sempat ngisi kolom id-nya — bikin NOT NULL constraint
        // violation. Composite primary key (collection_group_id, collection_id) itu pola standar
        // buat pivot table murni, jadi masalahnya nggak ada sama sekali.
        Schema::create('collection_group_items', function (Blueprint $table) {
            $table->foreignUuid('collection_group_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('collection_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['collection_group_id', 'collection_id']);
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->string('group_name', 100)->nullable();
        });

        Schema::dropIfExists('collection_group_items');
        Schema::dropIfExists('collection_groups');
    }
};
