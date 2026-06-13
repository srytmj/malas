<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Jobs\ScrapeJikanPageJob;
use App\Modules\Core\Models\Series;
use App\Modules\Jikan\Models\JikanSchedule;
use App\Modules\Jikan\Models\JikanScrapeSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class JikanController extends Controller
{
    public function index()
    {
        $schedules   = JikanSchedule::orderBy('sort_order')->get();
        $sessions    = JikanScrapeSession::with('schedule')->latest()->limit(15)->get();
        $active      = JikanScrapeSession::runningSession();
        $queuedCount = JikanScrapeSession::where('status', 'queued')->count();
        $totalSeries = Series::count();

        return view('admin.jikan.index', compact('schedules', 'sessions', 'active', 'queuedCount', 'totalSeries'));
    }

    public function status(): JsonResponse
    {
        $active      = JikanScrapeSession::runningSession();
        $queuedCount = JikanScrapeSession::where('status', 'queued')->count();

        $recentSeries = Series::latest('last_synced_at')
            ->limit(15)
            ->get(['id', 'title_romaji', 'title_english', 'status', 'total_volumes', 'score', 'last_synced_at']);

        return response()->json([
            'active'       => $active,
            'queuedCount'  => $queuedCount,
            'recentSeries' => $recentSeries,
            'totalSeries'  => Series::count(),
        ]);
    }

    public function scrapeNow(Request $request)
    {
        if (JikanScrapeSession::runningSession()) {
            return back()->with('error', 'Scrape sedang berjalan. Tunggu sampai selesai.');
        }

        $request->validate([
            'max_pages'  => 'nullable|integer|min:1|max:2000',
            'start_year' => 'nullable|integer|min:1990|max:2100',
            'end_year'   => 'nullable|integer|min:1990|max:2100',
        ]);

        $session = JikanScrapeSession::create([
            'status'       => 'pending',
            'triggered_by' => 'manual',
            'max_pages'    => $request->integer('max_pages', 200),
            'start_year'   => $request->start_year ?: null,
            'end_year'     => $request->end_year ?: null,
        ]);

        ScrapeJikanPageJob::dispatch($session->id, 1);

        return back()->with('success', 'Scrape dimulai! Data akan diupdate secara bertahap.');
    }

    public function storeSchedule(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'is_active'  => 'boolean',
            'hour'       => 'required|integer|min:0|max:23',
            'minute'     => 'required|integer|min:0|max:59',
            'max_pages'  => 'required|integer|min:1|max:2000',
            'start_year' => 'nullable|integer|min:1990|max:2100',
            'end_year'   => 'nullable|integer|min:1990|max:2100',
        ]);

        $maxOrder = JikanSchedule::max('sort_order') ?? 0;

        JikanSchedule::create([
            ...$data,
            'is_active'  => $request->boolean('is_active'),
            'start_year' => $data['start_year'] ?: null,
            'end_year'   => $data['end_year'] ?: null,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', "Jadwal \"{$data['name']}\" berhasil ditambahkan.");
    }

    public function updateSchedule(Request $request, JikanSchedule $schedule)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'is_active'  => 'boolean',
            'hour'       => 'required|integer|min:0|max:23',
            'minute'     => 'required|integer|min:0|max:59',
            'max_pages'  => 'required|integer|min:1|max:2000',
            'start_year' => 'nullable|integer|min:1990|max:2100',
            'end_year'   => 'nullable|integer|min:1990|max:2100',
        ]);

        $schedule->update([
            ...$data,
            'is_active'  => $request->boolean('is_active'),
            'start_year' => $data['start_year'] ?: null,
            'end_year'   => $data['end_year'] ?: null,
        ]);

        return back()->with('success', "Jadwal \"{$schedule->name}\" berhasil diupdate.");
    }

    public function destroySchedule(JikanSchedule $schedule)
    {
        $schedule->delete();
        return back()->with('success', 'Jadwal berhasil dihapus.');
    }

    public function reorderSchedules(Request $request): JsonResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
        foreach ($request->order as $index => $id) {
            JikanSchedule::where('id', (int) $id)->update(['sort_order' => $index]);
        }
        return response()->json(['ok' => true]);
    }

    public function cancelSession(JikanScrapeSession $session)
    {
        if ($session->isRunning()) {
            $session->update([
                'status'        => 'failed',
                'error_message' => 'Dibatalkan manual oleh admin',
                'completed_at'  => now(),
            ]);

            // Trigger next queued session
            $next = JikanScrapeSession::where('status', 'queued')
                ->leftJoin('jikan_schedules', 'jikan_scrape_sessions.schedule_id', '=', 'jikan_schedules.id')
                ->orderBy('jikan_schedules.sort_order')
                ->orderBy('jikan_scrape_sessions.created_at')
                ->select('jikan_scrape_sessions.*')
                ->first();

            if ($next) {
                $next->update(['status' => 'pending']);
                ScrapeJikanPageJob::dispatch($next->id, 1);
            }
        }

        return back()->with('success', 'Scrape dibatalkan.');
    }
}
