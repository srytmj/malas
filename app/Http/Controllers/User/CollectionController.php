<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\CollectionVolume;
use App\Services\StorageSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function __construct(private StorageSettingsService $storage) {}

    public function index(): Response
    {
        $collections = auth()->user()
            ->collections()
            ->with('series')
            ->withCount('collectionVolumes')
            ->latest()
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'series_id' => $c->series_id,
                'title_romaji' => $c->series->title_romaji,
                'title_english' => $c->series->title_english,
                'cover_url' => $this->storage->url($c->series->cover_path),
                'total_volumes' => $c->series->total_volumes,
                'collection_volumes_count' => $c->collection_volumes_count,
                'status' => $c->series->status,
                'type' => $c->series->type,
                'genres' => $c->series->genres ?? [],
                'condition' => $c->condition,
                'created_at' => $c->created_at->toIso8601String(),
                'is_adult' => $c->series->is_adult,
            ]);

        return Inertia::render('User/Collection/Index', [
            'collections' => $collections,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Collection::class);

        $request->validate([
            'series_ids' => ['required', 'array', 'min:1'],
            'series_ids.*' => ['uuid', 'exists:series,id'],
        ]);

        $user = auth()->user();
        $existingIds = $user->collections()->pluck('series_id')->toArray();

        $added = 0;
        $skipped = 0;

        foreach ($request->series_ids as $seriesId) {
            if (in_array($seriesId, $existingIds)) {
                $skipped++;

                continue;
            }
            $user->collections()->create(['series_id' => $seriesId]);
            $added++;
        }

        if ($added === 0) {
            return redirect()->back()
                ->with('info', 'Series yang dipilih sudah ada di koleksimu.');
        }

        $msg = "{$added} series berhasil ditambahkan ke koleksi."
            .($skipped > 0 ? " {$skipped} dilewati (sudah ada)." : '');

        return redirect()->back()->with('success', $msg);
    }

    public function show(Collection $collection): Response
    {
        $this->authorize('view', $collection);

        $series = $collection->series;

        $collectionVolumes = $collection->collectionVolumes()
            ->with(['activeLoans'])
            ->orderBy('volume_number')
            ->get()
            ->map(fn ($cv) => [
                'id' => $cv->id,
                'volume_number' => $cv->volume_number,
                'format' => $cv->format,
                'active_loan' => $cv->activeLoans->first() ? [
                    'id' => $cv->activeLoans->first()->id,
                    'borrower_name' => $cv->activeLoans->first()->borrower_name,
                    'loaned_at' => $cv->activeLoans->first()->loaned_at?->toDateString(),
                    'due_at' => $cv->activeLoans->first()->due_at?->toDateString(),
                    'is_overdue' => $cv->activeLoans->first()->isOverdue(),
                ] : null,
            ]);

        return Inertia::render('User/Collection/Show', [
            'collection' => [
                'id' => $collection->id,
                'series_id' => $collection->series_id,
                'condition' => $collection->condition,
            ],
            'series' => [
                ...$series->only([
                    'id', 'title_romaji', 'title_english', 'status', 'type', 'total_volumes', 'is_adult',
                ]),
                'genres' => $series->genres ?? [],
                'cover_url' => $this->storage->url($series->cover_path),
            ],
            'volumes' => $collectionVolumes,
        ]);
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $this->authorize('delete', $collection);

        $collection->delete();

        return redirect()->route('collection.index')
            ->with('success', 'Koleksi berhasil dihapus.');
    }

    public function updateCondition(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $request->validate([
            'condition' => ['required', Rule::in(['mint', 'good', 'fair', 'poor'])],
        ]);

        $collection->update(['condition' => $request->condition]);

        return redirect()->back()->with('success', 'Kondisi koleksi berhasil diperbarui.');
    }

    public function storeVolumes(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $request->validate([
            'volumes' => ['required', 'string'],
            'format' => ['required', Rule::in(['physical', 'ebook', 'online', 'webtoon'])],
        ]);

        $numbers = collect(explode(',', $request->volumes))
            ->flatMap(function (string $segment): array {
                $segment = trim($segment);
                if (str_contains($segment, '-')) {
                    [$a, $b] = array_map('intval', explode('-', $segment, 2));
                    if ($a > $b) {
                        [$a, $b] = [$b, $a];
                    }

                    return $a > 0 ? range($a, $b) : [];
                }
                $n = (int) $segment;

                return $n > 0 ? [$n] : [];
            })
            ->unique()
            ->sort()
            ->values();

        if ($numbers->isEmpty()) {
            return redirect()->back()->with('error', 'Masukkan minimal satu nomor volume yang valid.');
        }

        if ($numbers->count() > 100) {
            return redirect()->back()->with('error', 'Maksimal 100 volume sekaligus.');
        }

        $existing = $collection->collectionVolumes()->pluck('volume_number')->toArray();

        $created = 0;
        $skipped = 0;

        foreach ($numbers as $number) {
            if (in_array($number, $existing)) {
                $skipped++;

                continue;
            }
            $collection->collectionVolumes()->create([
                'volume_number' => $number,
                'format' => $request->format,
            ]);
            $created++;
        }

        $message = $created > 0
            ? "{$created} volume berhasil ditambahkan".($skipped > 0 ? ", {$skipped} dilewati (sudah ada)." : '.')
            : 'Semua volume yang diinput sudah ada di koleksimu.';

        return redirect()->back()->with($created > 0 ? 'success' : 'info', $message);
    }

    public function destroyVolume(Collection $collection, CollectionVolume $collectionVolume): RedirectResponse
    {
        $this->authorize('update', $collection);

        abort_if($collectionVolume->collection_id !== $collection->id, 403);

        $collectionVolume->delete();

        return redirect()->back()->with('success', 'Volume berhasil dihapus dari koleksi.');
    }

    public function destroyVolumes(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $request->validate([
            'volume_ids' => ['required', 'array', 'min:1'],
            'volume_ids.*' => ['uuid'],
        ]);

        $deleted = $collection->collectionVolumes()
            ->whereIn('id', $request->volume_ids)
            ->delete();

        return redirect()->back()->with('success', "{$deleted} volume berhasil dihapus dari koleksi.");
    }
}
