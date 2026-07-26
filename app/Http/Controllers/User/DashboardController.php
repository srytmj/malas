<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Loan;
use App\Models\Series;
use App\Models\Ticket;
use App\Models\User;
use App\Services\StorageSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private StorageSettingsService $storage) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $collectionIds = $user->collections()->pluck('id');

        $ownedCount = DB::table('collection_volumes')
            ->whereIn('collection_id', $collectionIds)
            ->count();

        $activeLoansCount = Loan::whereIn('collection_id', $collectionIds)
            ->whereNull('returned_at')
            ->count();

        $overdueCount = Loan::whereIn('collection_id', $collectionIds)
            ->whereNull('returned_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $latestTicket = Ticket::where('user_id', $user->id)
            ->latest()
            ->first(['id', 'subject', 'status']);

        $collectionsByStatus = Collection::where('user_id', $user->id)
            ->join('series', 'series.id', '=', 'collections.series_id')
            ->selectRaw('series.status as status, count(*) as total')
            ->groupBy('series.status')
            ->pluck('total', 'status')
            ->toArray();

        $recommendations = $this->genreRecommendations($user, 6);
        if ($recommendations->isEmpty()) {
            $recommendations = $this->fallbackRecommendations($user, 6);
        }
        $recommendations = $recommendations->map(fn ($s) => $this->presentSeries($s));

        return Inertia::render('User/Dashboard', [
            'stats' => [
                'series_count' => $collectionIds->count(),
                'owned_volumes_count' => $ownedCount,
                'active_loans_count' => $activeLoansCount,
                'overdue_count' => $overdueCount,
            ],
            'latest_ticket' => $latestTicket ? [
                'id' => $latestTicket->id,
                'subject' => $latestTicket->subject,
                'status' => $latestTicket->status,
            ] : null,
            'collections_by_status' => $collectionsByStatus,
            'recommendations' => $recommendations->values(),
        ]);
    }

    public function surpriseMe(Request $request): JsonResponse
    {
        $user = $request->user();

        $pool = $this->genreRecommendations($user, 20);

        if ($pool->isEmpty()) {
            $pool = $this->fallbackRecommendations($user, 20);
        }

        return response()->json([
            'series' => $pool->isNotEmpty() ? $this->presentSeries($pool->random()) : null,
        ]);
    }

    /**
     * Rank non-owned series by how many genres they share with the user's
     * existing collection. Computed in PHP (not raw JSON DB queries) so it
     * behaves identically on SQLite (dev) and MySQL (prod).
     *
     * @return SupportCollection<int, Series>
     */
    private function genreRecommendations(User $user, int $limit): SupportCollection
    {
        $ownedSeriesIds = $user->collections()->pluck('series_id');

        $ownedGenreCounts = Series::whereIn('id', $ownedSeriesIds)
            ->pluck('genres')
            ->flatten()
            ->filter()
            ->countBy();

        if ($ownedGenreCounts->isEmpty()) {
            return collect();
        }

        return Series::whereNotIn('id', $ownedSeriesIds)
            ->get(['id', 'title_romaji', 'cover_path', 'type', 'status', 'genres', 'authors', 'synopsis'])
            ->map(fn ($s) => [
                'series' => $s,
                'score' => collect($s->genres ?? [])->sum(fn ($g) => $ownedGenreCounts->get($g, 0)),
            ])
            ->filter(fn ($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('series')
            ->values();
    }

    /**
     * Used when genre-overlap scoring finds nothing (new user, or the
     * remaining catalog lacks genre metadata) so the section never sits empty.
     *
     * @return SupportCollection<int, Series>
     */
    private function fallbackRecommendations(User $user, int $limit): SupportCollection
    {
        $ownedSeriesIds = $user->collections()->pluck('series_id');

        return Series::whereNotIn('id', $ownedSeriesIds)
            ->inRandomOrder()
            ->limit($limit)
            ->get(['id', 'title_romaji', 'cover_path', 'type', 'status', 'genres', 'authors', 'synopsis']);
    }

    private function presentSeries(Series $series): array
    {
        $synopsis = $series->synopsis ? trim(strip_tags($series->synopsis)) : null;

        return [
            'id' => $series->id,
            'title_romaji' => $series->title_romaji,
            'cover_url' => $this->storage->url($series->cover_path),
            'type' => $series->type,
            'status' => $series->status,
            'authors' => $series->authors ?? [],
            'genres' => $series->genres ?? [],
            'synopsis' => $synopsis ? Str::limit($synopsis, 160) : null,
        ];
    }
}
