<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jikan_scrape_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->string('triggered_by', 50)->default('manual');
            $table->unsignedSmallInteger('max_pages')->default(200);
            $table->unsignedInteger('current_page')->default(0);
            $table->unsignedInteger('total_pages')->default(0);
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('new_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('jikan_scrape_schedule', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->unsignedTinyInteger('hour')->default(3);
            $table->unsignedTinyInteger('minute')->default(0);
            $table->unsignedSmallInteger('max_pages')->default(200);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jikan_scrape_schedule');
        Schema::dropIfExists('jikan_scrape_sessions');
    }
};
