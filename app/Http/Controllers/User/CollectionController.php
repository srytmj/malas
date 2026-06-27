<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Series;
use App\Models\Volume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(): Response
    {
        $collections = auth()->user()
            ->collections()
            ->with('series')
            ->withCount(['volumes', 'ownedVolumes'])
            ->latest()
            ->get()
            ->map(fn ($c) => [
                'id'                 => $c->id,
                'series_id'          => $c->series_id,
                'title_romaji'       => $c->series->title_romaji,
                'title_english'      => $c->series->title_english,
                'cover_url'          => $c->series->cover_path
                    ? Storage::url($c->series->cover_path)
                    : null,
                'total_volumes'      => $c->series->total_volumes,
                'volumes_count'      => $c->volumes_count,
                'owned_volumes_count' => $c->owned_volumes_count,
                'status'             => $c->series->status,
                'type'               => $c->series->type,
                'acquired_at'        => $c->acquired_at?->toDateString(),
                'notes'              => $c->notes,
            ]);

        return Inertia::render('User/Collection/Index', [
            'collections' => $collections,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Collection::class);

        $request->validate([
            'series_id' => ['required', 'uuid', 'exists:series,id'],
        ]);

        $existing = auth()->user()
            ->collections()
            ->where('series_id', $request->series_id)
            ->exists();

        if ($existing) {
            return redirect()->back()->with('error', 'Series sudah ada di koleksimu.');
        }

        $collection = auth()->user()->collections()->create([
            'series_id' => $request->series_id,
        ]);

        // Sync semua volume yang ada dengan is_owned = false
        $volumeIds = Series::find($request->series_id)
            ->volumes()
            ->pluck('id');

        $collection->volumes()->attach($volumeIds->mapWithKeys(
            fn ($id) => [$id => ['is_owned' => false]]
        )->toArray());

        return redirect()->route('collection.show', $collection)
            ->with('success', 'Series berhasil ditambahkan ke koleksi.');
    }

    public function show(Collection $collection): Response
    {
        $this->authorize('view', $collection);

        $series = $collection->series;

        // Ambil semua volume series + status is_owned dari pivot
        $allVolumes = $series->volumes()
            ->orderBy('volume_number')
            ->get(['id', 'volume_number', 'type', 'isbn', 'published_at', 'cover_path']);

        $ownedMap = $collection->volumes()
            ->pluck('collection_volumes.is_owned', 'volumes.id')
            ->toArray();

        $activeLoanMap = $collection->loans()
            ->whereNull('returned_at')
            ->get()
            ->keyBy('volume_id')
            ->map(fn ($l) => [
                'id'            => $l->id,
                'borrower_name' => $l->borrower_name,
                'loaned_at'     => $l->loaned_at?->toDateString(),
                'due_at'        => $l->due_at?->toDateString(),
                'is_overdue'    => $l->isOverdue(),
            ]);

        $volumes = $allVolumes->map(fn ($v) => [
            'id'            => $v->id,
            'volume_number' => $v->volume_number,
            'type'          => $v->type,
            'isbn'          => $v->isbn,
            'published_at'  => $v->published_at?->toDateString(),
            'cover_url'     => $v->cover_path ? Storage::url($v->cover_path) : null,
            'is_owned'      => (bool) ($ownedMap[$v->id] ?? false),
            'active_loan'   => $activeLoanMap[$v->id] ?? null,
        ]);

        return Inertia::render('User/Collection/Show', [
            'collection' => [
                'id'          => $collection->id,
                'series_id'   => $collection->series_id,
                'acquired_at' => $collection->acquired_at?->toDateString(),
                'notes'       => $collection->notes,
            ],
            'series' => [
                ...$series->only([
                    'id', 'title_romaji', 'title_english', 'status', 'type', 'total_volumes',
                ]),
                'cover_url' => $series->cover_path ? Storage::url($series->cover_path) : null,
            ],
            'volumes'      => $volumes,
            'owned_count'  => $volumes->filter(fn ($v) => $v['is_owned'])->count(),
        ]);
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $this->authorize('delete', $collection);

        $collection->volumes()->detach();
        $collection->delete();

        return redirect()->route('collection.index')
            ->with('success', 'Koleksi berhasil dihapus.');
    }

    public function toggleVolume(Collection $collection, Volume $volume): RedirectResponse
    {
        $this->authorize('update', $collection);

        $existing = $collection->volumes()
            ->where('volumes.id', $volume->id)
            ->first();

        if ($existing) {
            $collection->volumes()->updateExistingPivot($volume->id, [
                'is_owned' => !$existing->pivot->is_owned,
            ]);
        } else {
            $collection->volumes()->attach($volume->id, ['is_owned' => true]);
        }

        return redirect()->back();
    }
}
