<?php

use App\Jobs\ScrapeJikanPageJob;
use App\Modules\Jikan\Models\JikanSchedule;
use App\Modules\Jikan\Models\JikanScrapeSession;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Multi-schedule Jikan scraper — runs every minute, fires schedules at their configured hour:minute
Schedule::call(function () {
    try {
        $now = now();

        $schedules = JikanSchedule::where('is_active', true)
            ->where('hour', $now->hour)
            ->where('minute', $now->minute)
            ->orderBy('sort_order')
            ->get();

        foreach ($schedules as $schedule) {
            // Skip if already ran today
            if ($schedule->last_run_at?->isToday()) continue;

            // Skip if already queued/pending/running today for this schedule
            $alreadyQueued = JikanScrapeSession::where('schedule_id', $schedule->id)
                ->whereDate('created_at', today())
                ->whereIn('status', ['pending', 'queued', 'running'])
                ->exists();
            if ($alreadyQueued) continue;

            $hasRunning = JikanScrapeSession::whereIn('status', ['pending', 'running'])->exists();

            $session = JikanScrapeSession::create([
                'schedule_id'  => $schedule->id,
                'status'       => $hasRunning ? 'queued' : 'pending',
                'triggered_by' => 'schedule',
                'max_pages'    => $schedule->max_pages,
                'start_year'   => $schedule->start_year,
                'end_year'     => $schedule->end_year,
            ]);

            if (!$hasRunning) {
                ScrapeJikanPageJob::dispatch($session->id, 1);
            }
        }
    } catch (\Throwable) {
        // Swallow errors so schedule doesn't spam logs
    }
})->everyMinute()->name('jikan-auto-scrape')->withoutOverlapping();
