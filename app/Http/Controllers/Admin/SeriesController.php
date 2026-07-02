<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeriesRequest;
use App\Http\Requests\Admin\UpdateSeriesRequest;
use App\Models\CollectionVolume;
use App\Models\Series;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SeriesController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Series::class);

        $series = Series::query()
            ->when(request('search'), fn ($q, $s) =>
                $q->where(fn ($sub) =>
                    $sub->where('title_romaji', 'like', "%{$s}%")
                        ->orWhere('title_english', 'like', "%{$s}%")
                ))
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->when(request('type'),   fn ($q, $t) => $q->where('type', $t))
            ->withCount('volumes')
            ->latest()
            ->paginate(20)
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

        return Inertia::render('Admin/Series/Index', [
            'series'  => $series,
            'filters' => request()->only(['search', 'status', 'type']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Series::class);

        return Inertia::render('Admin/Series/Create');
    }

    public function store(StoreSeriesRequest $request): RedirectResponse
    {
        $this->authorize('create', Series::class);

        $data              = $request->validated();
        $data['cover_path'] = $this->storeCover($request->file('cover'));
        unset($data['cover']);

        Series::create($data);

        return redirect()->route('admin.series.index')
            ->with('success', 'Series berhasil ditambahkan.');
    }

    public function show(Request $request, Series $series): Response
    {
        $this->authorize('view', $series);

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

        return Inertia::render('Admin/Series/Show', [
            'series'  => [
                ...$series->only([
                    'id', 'title_romaji', 'title_english', 'title_japanese',
                    'synopsis', 'status', 'type', 'total_volumes', 'score', 'rank',
                ]),
                'published_from' => $series->published_from?->toDateString(),
                'published_to'   => $series->published_to?->toDateString(),
                'cover_url'      => $series->cover_path ? Storage::url($series->cover_path) : null,
            ],
            'volumes' => $volumes,
            'can'     => [
                'update'       => $request->user()->can('update', $series),
                'delete'       => $request->user()->can('delete', $series),
                'createVolume' => $request->user()->can('create', \App\Models\Volume::class),
            ],
            'ownerships' => CollectionVolume::whereHas('collection', fn ($q) => $q->where('series_id', $series->id))
                ->with(['collection.user', 'activeLoans'])
                ->orderBy('volume_number')
                ->get()
                ->map(fn ($cv) => [
                    'id'            => $cv->id,
                    'volume_number' => $cv->volume_number,
                    'format'        => $cv->format,
                    'user_name'     => $cv->collection->user->name,
                    'active_loan'   => $cv->activeLoans->first()
                        ? ['borrower_name' => $cv->activeLoans->first()->borrower_name]
                        : null,
                ]),
        ]);
    }

    public function edit(Series $series): Response
    {
        $this->authorize('update', $series);

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

        return Inertia::render('Admin/Series/Edit', [
            'series' => [
                ...$series->only([
                    'id', 'mal_id', 'title_romaji', 'title_english', 'title_japanese',
                    'synopsis', 'status', 'type', 'total_volumes', 'score', 'rank',
                ]),
                'published_from' => $series->published_from?->toDateString(),
                'published_to'   => $series->published_to?->toDateString(),
                'cover_url'      => $series->cover_path ? Storage::url($series->cover_path) : null,
            ],
            'volumes' => $volumes,
        ]);
    }

    public function update(UpdateSeriesRequest $request, Series $series): RedirectResponse
    {
        $this->authorize('update', $series);

        $data = $request->validated();

        if ($request->hasFile('cover')) {
            if ($series->cover_path) {
                Storage::disk('public')->delete($series->cover_path);
            }
            $data['cover_path'] = $this->storeCover($request->file('cover'));
        } elseif ($request->filled('cover_url')) {
            $fetched = $this->fetchCoverFromUrl($request->cover_url);
            if ($fetched) {
                if ($series->cover_path) {
                    Storage::disk('public')->delete($series->cover_path);
                }
                $data['cover_path'] = $fetched;
            }
        }

        unset($data['cover'], $data['cover_url']);
        $series->update($data);

        return redirect()->route('admin.series.show', $series)
            ->with('success', 'Series berhasil diperbarui.');
    }

    public function destroy(Series $series): RedirectResponse
    {
        $this->authorize('delete', $series);
        $series->delete();

        return redirect()->route('admin.series.index')
            ->with('success', 'Series berhasil dihapus.');
    }

    private function storeCover(?UploadedFile $file): ?string
    {
        return $file?->store('covers', 'public');
    }

    private function fetchCoverFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(20)->get($url);
            if (! $response->successful()) return null;

            $ext  = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $path = 'covers/url_' . uniqid() . '.' . $ext;

            Storage::disk(config('filesystems.cover_disk', 'public'))->put($path, $response->body());

            return $path;
        } catch (\Exception) {
            return null;
        }
    }
}
