<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('storage_settings', function (Blueprint $table) {
            $table->enum('migration_status', ['idle', 'running', 'completed', 'failed'])->default('idle')->after('url');
            $table->text('migration_message')->nullable()->after('migration_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('storage_settings', function (Blueprint $table) {
            $table->dropColumn(['migration_status', 'migration_message']);
        });
    }
};
