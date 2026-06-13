<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rename single-schedule table to multi-schedule
        Schema::rename('jikan_scrape_schedule', 'jikan_schedules');

        // Add new columns to jikan_schedules
        Schema::table('jikan_schedules', function (Blueprint $table) {
            $table->string('name', 100)->default('Jadwal Default')->after('id');
            $table->unsignedSmallInteger('start_year')->nullable()->after('max_pages');
            $table->unsignedSmallInteger('end_year')->nullable()->after('start_year');
            $table->unsignedInteger('sort_order')->default(0)->after('end_year');
        });

        // Add columns to jikan_scrape_sessions
        Schema::table('jikan_scrape_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('schedule_id')->nullable()->after('id');
            $table->unsignedSmallInteger('start_year')->nullable()->after('triggered_by');
            $table->unsignedSmallInteger('end_year')->nullable()->after('start_year');
        });

        // Extend status enum to include 'queued'
        DB::statement(
            "ALTER TABLE jikan_scrape_sessions MODIFY COLUMN `status`
             ENUM('pending','queued','running','completed','failed') DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE jikan_scrape_sessions MODIFY COLUMN `status`
             ENUM('pending','running','completed','failed') DEFAULT 'pending'"
        );

        Schema::table('jikan_scrape_sessions', function (Blueprint $table) {
            $table->dropColumn(['schedule_id', 'start_year', 'end_year']);
        });

        Schema::table('jikan_schedules', function (Blueprint $table) {
            $table->dropColumn(['name', 'start_year', 'end_year', 'sort_order']);
        });

        Schema::rename('jikan_schedules', 'jikan_scrape_schedule');
    }
};
