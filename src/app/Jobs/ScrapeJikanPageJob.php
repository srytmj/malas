<?php

namespace App\Jobs;

use App\Modules\Jikan\JikanService;
use App\Modules\Jikan\Models\JikanSchedule;
use App\Modules\Jikan\Models\JikanScrapeSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScrapeJikanPageJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;
    public int $tries   = 3;

    public function __construct(
        public readonly string $sessionId,
        public readonly int    $page = 1,
    ) {}

    public function handle(JikanService $jikan): void
    {
        $session = JikanScrapeSession::find($this->sessionId);
        if (!$session || !$session->isRunning()) return;

        if ($this->page === 1) {
            $session->update(['status' => 'running', 'started_at' => now()]);
        }

        try {
            $response   = $jikan->getMangaList($this->page, $session->start_year, $session->end_year);
            $data       = $response['data'] ?? [];
            $pagination = $response['pagination'] ?? [];

            if ($this->page === 1) {
                $totalPages = min(
                    $pagination['last_visible_page'] ?? 1,
                    $session->max_pages
                );
                $session->update([
                    'total_pages' => $totalPages,
                    'total_count' => $pagination['items']['total'] ?? 0,
                ]);
            }

            [$newCount, $updatedCount] = $jikan->upsertPage($data);

            $session->increment('processed_count', count($data));
            $session->increment('new_count',       $newCount);
            $session->increment('updated_count',   $updatedCount);
            $session->update(['current_page' => $this->page]);

            $session->refresh();
            $nextPage = $this->page + 1;

            if (!empty($data) && $nextPage <= $session->total_pages) {
                static::dispatch($this->sessionId, $nextPage)
                    ->delay(now()->addSeconds(1));
            } else {
                $session->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);

                // Update schedule last_run_at
                if ($session->schedule_id) {
                    JikanSchedule::where('id', $session->schedule_id)->update(['last_run_at' => now()]);
                }

                // Start next queued session (ordered by schedule sort_order)
                $this->dispatchNextQueued();
            }
        } catch (\Throwable $e) {
            $session->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);
            $this->dispatchNextQueued();
        }
    }

    public function failed(\Throwable $exception): void
    {
        $session = JikanScrapeSession::find($this->sessionId);
        $session?->update([
            'status'        => 'failed',
            'error_message' => $exception->getMessage(),
            'completed_at'  => now(),
        ]);
        $this->dispatchNextQueued();
    }

    private function dispatchNextQueued(): void
    {
        $next = JikanScrapeSession::where('status', 'queued')
            ->leftJoin('jikan_schedules', 'jikan_scrape_sessions.schedule_id', '=', 'jikan_schedules.id')
            ->orderBy('jikan_schedules.sort_order')
            ->orderBy('jikan_scrape_sessions.created_at')
            ->select('jikan_scrape_sessions.*')
            ->first();

        if ($next) {
            $next->update(['status' => 'pending']);
            static::dispatch($next->id, 1);
        }
    }
}
