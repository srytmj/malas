<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->bigInteger('ranobedb_id')->unique()->nullable()->after('anilist_id');
            $table->json('illustrators')->nullable()->after('authors');
        });
    }

    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn(['ranobedb_id', 'illustrators']);
        });
    }
};
