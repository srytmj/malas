<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['volume_id']);
            $table->dropColumn('volume_id');
            $table->foreignUuid('collection_volume_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['collection_volume_id']);
            $table->dropColumn('collection_volume_id');
            $table->foreignUuid('volume_id')->constrained()->cascadeOnDelete();
        });
    }
};
