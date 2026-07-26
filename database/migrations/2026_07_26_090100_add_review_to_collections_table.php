<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->smallInteger('personal_rating')->nullable()->after('notes');
            $table->text('personal_review')->nullable()->after('personal_rating');
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['personal_rating', 'personal_review']);
        });
    }
};
