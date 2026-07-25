<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Services\StorageSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeriesController extends Controller
{
    public function __construct(private StorageSettingsService $storage) {}

    public function index(): Response
    {
        $series = Series::query()
            ->when(request('search'), fn ($q, $s) => $q->where(fn ($sub) => $sub->where('title_romaji', 'like', "%{$s}%")
                ->orWhere('title_english', 'like', "%{$s}%")
            ))
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(request('type'), fn ($q, $t) => $q->where('type', $t))
            ->withCount('volumes')
            ->latest()
            ->paginate(24)
            ->withQueryString()
            ->through(fn ($s) => [
                'id' => $s->id,
                'title_romaji' => $s->title_romaji,
                'title_english' => $s->title_english,
                'cover_url' => $this->storage->url($s->cover_path),
                'status' => $s->status,
                'type' => $s->type,
                'total_volumes' => $s->total_volumes,
                'volumes_count' => $s->volumes_count,
                'score' => $s->score,
                'is_adult' => $s->is_adult,
            ]);

        // Cek series mana yang sudah ada di koleksi user
        $collectionSeriesIds = auth()->user()
            ->collections()
            ->pluck('series_id')
            ->toArray();

        return Inertia::render('User/Catalog/Index', [
            'series' => $series,
            'collectionSeriesIds' => $collectionSeriesIds,
            'filters' => request()->only(['search', 'status', 'type']),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        $series = Series::when($q, fn ($query) => $query->where(fn ($sub) => $sub->where('title_romaji', 'like', "%{$q}%")
            ->orWhere('title_english', 'like', "%{$q}%")
        )
        )
            ->latest()
            ->limit(24)
            ->get(['id', 'title_romaji', 'title_english', 'cover_path', 'type', 'status', 'is_adult'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'title_romaji' => $s->title_romaji,
                'title_english' => $s->title_english,
                'cover_url' => $this->storage->url($s->cover_path),
                'type' => $s->type,
                'status' => $s->status,
                'is_adult' => $s->is_adult,
            ]);

        $collectionSeriesIds = auth()->user()->collections()->pluck('series_id')->toArray();

        return response()->json([
            'results' => $series->toArray(),
            'collection_series_ids' => $collectionSeriesIds,
        ]);
    }

    public function show(Series $series): Response
    {
        $volumes = $series->volumes()
            ->orderBy('volume_number')
            ->get(['id', 'volume_number', 'type', 'isbn', 'published_at', 'cover_path'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'volume_number' => $v->volume_number,
                'type' => $v->type,
                'isbn' => $v->isbn,
                'published_at' => $v->published_at?->toDateString(),
                'cover_url' => $this->storage->url($v->cover_path),
            ]);

        $collection = auth()->user()
            ->collections()
            ->where('series_id', $series->id)
            ->first();

        return Inertia::render('User/Catalog/Show', [
            'series' => [
                ...$series->only([
                    'id', 'anilist_id', 'title_romaji', 'title_english', 'title_japanese',
                    'synopsis', 'status', 'type', 'total_volumes', 'score', 'rank',
                    'genres', 'authors', 'themes', 'demographics', 'is_adult',
                ]),
                'published_from' => $series->published_from?->toDateString(),
                'published_to' => $series->published_to?->toDateString(),
                'cover_url' => $this->storage->url($series->cover_path),
            ],
            'volumes' => $volumes,
            'media' => $series->media->map(fn ($m) => [
                'id' => $m->id,
                'image_url' => $this->storage->url($m->image_path),
                'caption' => $m->caption,
            ]),
            'collection' => $collection ? ['id' => $collection->id] : null,
        ]);
    }
}
