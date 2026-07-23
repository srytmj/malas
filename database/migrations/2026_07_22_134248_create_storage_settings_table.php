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
        Schema::create('storage_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('driver', ['local', 's3'])->default('local');
            $table->string('access_key_id')->nullable();
            $table->text('secret_access_key')->nullable();
            $table->string('bucket')->nullable();
            $table->string('endpoint')->nullable();
            $table->string('region')->nullable();
            $table->string('url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_settings');
    }
};
