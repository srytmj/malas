<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Series;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SeriesController extends Controller
{
    public function index(): Response
    {
        $series = Series::query()
            ->when(request('search'), fn ($q, $s) =>
                $q->where('title_romaji', 'like', "%{$s}%")
                  ->orWhere('title_english', 'like', "%{$s}%"))
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(request('type'),   fn ($q, $t) => $q->where('type', $t))
            ->withCount('volumes')
            ->latest()
            ->paginate(24)
            ->withQueryString()
            ->through(fn ($s) => [
                'id'            => $s->id,
                'title_romaji'  => $s->title_romaji,
                'title_english' => $s->title_english,
                'cover_url'     => $s->cover_path ? Storage::url($s->cover_path) : null,
                'status'        => $s->status,
                'type'          => $s->type,
                'total_volumes' => $s->total_volumes,
                'volumes_count' => $s->volumes_count,
                'score'         => $s->score,
            ]);

        // Cek series mana yang sudah ada di koleksi user
        $collectionSeriesIds = auth()->user()
            ->collections()
            ->pluck('series_id')
            ->toArray();

        return Inertia::render('User/Catalog/Index', [
            'series'              => $series,
            'collectionSeriesIds' => $collectionSeriesIds,
            'filters'             => request()->only(['search', 'status', 'type']),
        ]);
    }

    public function show(Series $series): Response
    {
        $volumes = $series->volumes()
            ->orderBy('volume_number')
            ->get(['id', 'volume_number', 'type', 'isbn', 'published_at', 'cover_path'])
            ->map(fn ($v) => [
                'id'            => $v->id,
                'volume_number' => $v->volume_number,
                'type'          => $v->type,
                'isbn'          => $v->isbn,
                'published_at'  => $v->published_at?->toDateString(),
                'cover_url'     => $v->cover_path ? Storage::url($v->cover_path) : null,
            ]);

        $collection = auth()->user()
            ->collections()
            ->where('series_id', $series->id)
            ->first();

        return Inertia::render('User/Catalog/Show', [
            'series' => [
                ...$series->only([
                    'id', 'title_romaji', 'title_english', 'title_japanese',
                    'synopsis', 'status', 'type', 'total_volumes', 'score', 'rank',
                ]),
                'published_from' => $series->published_from?->toDateString(),
                'published_to'   => $series->published_to?->toDateString(),
                'cover_url'      => $series->cover_path ? Storage::url($series->cover_path) : null,
            ],
            'volumes'    => $volumes,
            'collection' => $collection ? ['id' => $collection->id] : null,
        ]);
    }
}
