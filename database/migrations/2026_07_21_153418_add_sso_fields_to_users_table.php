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
        Schema::table('users', function (Blueprint $table) {
            $table->string('sso_id')->nullable()->unique()->after('id');
            $table->string('username')->nullable()->after('name');
            $table->string('avatar')->nullable()->after('email');
            $table->string('password')->nullable()->change();
            $table->dropColumn('name_changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['sso_id']);
            $table->dropColumn(['sso_id', 'username', 'avatar']);
            $table->string('password')->nullable(false)->change();
            $table->timestamp('name_changed_at')->nullable();
        });
    }
};
