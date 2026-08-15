<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collection_groups', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->boolean('is_public')->default(false)->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('collection_groups', function (Blueprint $table) {
            $table->dropColumn(['slug', 'is_public']);
        });
    }
};
