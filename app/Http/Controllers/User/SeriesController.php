<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Series;
use App\Services\StorageSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeriesController extends Controller
{
    // "Manga group" mencakup semua tipe komik; light novel berdiri sendiri karena
    // vocab genre-nya (dari RanobeDB) cenderung berbeda dari manga (dari AniList).
    private const MANGA_TYPES = ['manga', 'manhwa', 'manhua', 'one_shot', 'doujinshi'];

    public function __construct(private StorageSettingsService $storage) {}

    public function index(): Response
    {
        // Cek series mana yang sudah ada di koleksi user
        $collectionSeriesIds = auth()->user()
            ->collections()
            ->pluck('series_id')
            ->toArray();

        $series = Series::query()
            ->when(request('search'), fn ($q, $s) => $q->where(fn ($sub) => $sub->where('title_romaji', 'like', "%{$s}%")
                ->orWhere('title_english', 'like', "%{$s}%")
            ))
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(request('type'), fn ($q, $t) => $q->where('type', $t))
            // Multi-select genre: OR-match — series lolos kalau punya salah satu genre yang dipilih,
            // bukan wajib semuanya (biar hasilnya nggak keburu kosong pas user pilih beberapa genre).
            ->when(array_filter((array) request('genre', [])), fn ($q, $genres) => $q->where(function ($sub) use ($genres) {
                foreach ($genres as $genre) {
                    $sub->orWhereJsonContains('genres', $genre);
                }
            }))
            ->when(request('ownership') === 'owned', fn ($q) => $q->whereIn('id', $collectionSeriesIds))
            ->when(request('ownership') === 'not_owned', fn ($q) => $q->whereNotIn('id', $collectionSeriesIds))
            ->withCount('volumes')
            ->latest()
            ->paginate(24)
            ->withQueryString()
            ->through(fn ($s) => [
                'id' => $s->id,
                'slug' => $s->slug,
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

        return Inertia::render('User/Catalog/Index', [
            'series' => $series,
            'collectionSeriesIds' => $collectionSeriesIds,
            'genreOptions' => $this->genreOptions(),
            'filters' => [
                ...request()->only(['search', 'status', 'type', 'ownership']),
                'genre' => array_values(array_filter((array) request('genre', []))),
            ],
        ]);
    }

    /** @return array{manga: array<int, string>, novel: array<int, string>} */
    private function genreOptions(): array
    {
        $flatten = fn ($type) => Series::query()
            ->when($type === 'novel', fn ($q) => $q->where('type', 'novel'))
            ->when($type === 'manga', fn ($q) => $q->whereIn('type', self::MANGA_TYPES))
            ->whereNotNull('genres')
            ->pluck('genres')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'manga' => $flatten('manga'),
            'novel' => $flatten('novel'),
        ];
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
            ->get(['id', 'slug', 'title_romaji', 'title_english', 'cover_path', 'type', 'status', 'is_adult'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'slug' => $s->slug,
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

        $wishlistItem = auth()->user()
            ->wishlistItems()
            ->where('series_id', $series->id)
            ->first();

        $collectorsCount = Collection::where('series_id', $series->id)->count();
        $collectors = Collection::where('series_id', $series->id)
            ->with('user:id,username,avatar')
            ->limit(8)
            ->get()
            ->pluck('user')
            ->filter()
            ->map(fn ($u) => ['id' => $u->id, 'username' => $u->username, 'avatar' => $u->avatar])
            ->values();

        return Inertia::render('User/Catalog/Show', [
            'series' => [
                ...$series->only([
                    'id', 'slug', 'anilist_id', 'title_romaji', 'title_english', 'title_japanese',
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
            'wishlist_item' => $wishlistItem ? ['id' => $wishlistItem->id] : null,
            'collectors' => [
                'avatars' => $collectors,
                'count' => $collectorsCount,
            ],
        ]);
    }
}
