<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_volumes', function (Blueprint $table) {
            $table->string('ebook_source')->nullable()->after('format');
            $table->string('language')->nullable()->after('ebook_source');
        });
    }

    public function down(): void
    {
        Schema::table('collection_volumes', function (Blueprint $table) {
            $table->dropColumn(['ebook_source', 'language']);
        });
    }
};
