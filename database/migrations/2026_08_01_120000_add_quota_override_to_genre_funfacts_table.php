<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('genre_funfacts', function (Blueprint $table) {
            $table->unsignedInteger('quota_override')->nullable()->after('manual_regenerate_count');
        });
    }

    public function down(): void
    {
        Schema::table('genre_funfacts', function (Blueprint $table) {
            $table->dropColumn('quota_override');
        });
    }
};
