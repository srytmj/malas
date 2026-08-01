<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
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
            ->withCount([
                'collectionVolumes',
                'collectionVolumes as read_volumes_count' => fn ($q) => $q->whereNotNull('read_at'),
            ])
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
                'read_volumes_count' => $c->read_volumes_count,
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
        $createdIds = [];

        foreach ($request->series_ids as $seriesId) {
            if (in_array($seriesId, $existingIds)) {
                $skipped++;

                continue;
            }
            $collection = $user->collections()->create(['series_id' => $seriesId]);
            $createdIds[] = $collection->id;
            $user->wishlistItems()->where('series_id', $seriesId)->delete();
            $added++;
        }

        if ($added === 0) {
            return redirect()->back()
                ->with('info', 'Series yang dipilih sudah ada di koleksimu.');
        }

        $msg = "{$added} series berhasil ditambahkan ke koleksi."
            .($skipped > 0 ? " {$skipped} dilewati (sudah ada)." : '');

        ActivityLog::record('collection.add', "{$user->name} menambahkan {$added} series ke koleksi.", $user);

        return redirect()->back()->with([
            'success' => $msg,
            'undo_url' => route('collection.undo-store'),
            'undo_payload' => ['collection_ids' => $createdIds],
        ]);
    }

    public function undoStore(Request $request): RedirectResponse
    {
        $request->validate([
            'collection_ids' => ['required', 'array', 'min:1'],
            'collection_ids.*' => ['uuid'],
        ]);

        $user = auth()->user();
        $count = $user->collections()->whereIn('id', $request->collection_ids)->delete();

        ActivityLog::record('collection.undo_add', "{$user->name} membatalkan penambahan {$count} series ke koleksi.", $user);

        return redirect()->back()->with('success', "{$count} series dibatalkan penambahannya.");
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
                'ebook_source' => $cv->ebook_source,
                'language' => $cv->language,
                'read_at' => $cv->read_at?->toIso8601String(),
                'active_loan' => $cv->activeLoans->first() ? [
                    'id' => $cv->activeLoans->first()->id,
                    'borrower_name' => $cv->activeLoans->first()->borrower_name,
                    'loaned_at' => $cv->activeLoans->first()->loaned_at?->toDateString(),
                    'due_at' => $cv->activeLoans->first()->due_at?->toDateString(),
                    'is_overdue' => $cv->activeLoans->first()->isOverdue(),
                ] : null,
            ]);

        $lastReadVolume = $collectionVolumes->filter(fn ($cv) => $cv['read_at'] !== null)
            ->max('volume_number');

        return Inertia::render('User/Collection/Show', [
            'collection' => [
                'id' => $collection->id,
                'series_id' => $collection->series_id,
                'condition' => $collection->condition,
                'personal_rating' => $collection->personal_rating,
                'personal_review' => $collection->personal_review,
            ],
            'series' => [
                ...$series->only([
                    'id', 'title_romaji', 'title_english', 'status', 'type', 'total_volumes', 'is_adult',
                ]),
                'genres' => $series->genres ?? [],
                'themes' => $series->themes ?? [],
                'demographics' => $series->demographics ?? [],
                'cover_url' => $this->storage->url($series->cover_path),
            ],
            'volumes' => $collectionVolumes,
            'last_read_volume' => $lastReadVolume,
        ]);
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $this->authorize('delete', $collection);

        $payload = [
            'series_id' => $collection->series_id,
            'condition' => $collection->condition,
            'personal_rating' => $collection->personal_rating,
            'personal_review' => $collection->personal_review,
            'volumes' => $collection->collectionVolumes()->get([
                'volume_number', 'format', 'ebook_source', 'language', 'read_at',
            ])->map->only(['volume_number', 'format', 'ebook_source', 'language', 'read_at'])->all(),
        ];

        $seriesTitle = $collection->series->title_romaji;
        $collection->delete();

        ActivityLog::record('collection.remove', auth()->user()->name." menghapus \"{$seriesTitle}\" dari koleksi.", auth()->user());

        return redirect()->route('collection.index')->with([
            'success' => 'Koleksi berhasil dihapus.',
            // Catatan: riwayat pinjaman (Loan) volume di koleksi ini tidak ikut dipulihkan oleh undo.
            'undo_url' => route('collection.undo-destroy'),
            'undo_payload' => $payload,
        ]);
    }

    public function undoDestroy(Request $request): RedirectResponse
    {
        $request->validate([
            'series_id' => ['required', 'uuid', 'exists:series,id'],
            'condition' => ['nullable', 'string'],
            'personal_rating' => ['nullable', 'integer'],
            'personal_review' => ['nullable', 'string'],
            'volumes' => ['array'],
        ]);

        $user = auth()->user();

        if ($user->collections()->where('series_id', $request->series_id)->exists()) {
            return redirect()->back()->with('info', 'Series ini sudah ada lagi di koleksimu.');
        }

        // array_filter buang key yang nilainya null, supaya default kolom DB (mis. condition) tetap
        // kepakai — kalau nggak, Eloquent akan insert NULL secara eksplisit dan melanggar NOT NULL.
        $attributes = array_filter($request->only([
            'series_id', 'condition', 'personal_rating', 'personal_review',
        ]), fn ($value) => $value !== null);

        $collection = $user->collections()->create($attributes);

        foreach ($request->input('volumes', []) as $volume) {
            $collection->collectionVolumes()->create(array_filter($volume, fn ($value) => $value !== null));
        }

        ActivityLog::record('collection.undo_remove', "{$user->name} memulihkan koleksi yang dihapus.", $user);

        return redirect()->back()->with('success', 'Koleksi berhasil dipulihkan.');
    }

    public function updateCondition(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $request->validate([
            'condition' => ['required', Rule::in(['mint', 'good', 'fair', 'poor'])],
        ]);

        $collection->update(['condition' => $request->condition]);

        ActivityLog::record('collection.update_condition', auth()->user()->name." mengubah kondisi koleksi \"{$collection->series->title_romaji}\" menjadi {$request->condition}.", auth()->user());

        return redirect()->back()->with('success', 'Kondisi koleksi berhasil diperbarui.');
    }

    public function storeVolumes(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $request->validate([
            'volumes' => ['required', 'string'],
            'format' => ['required', Rule::in(['physical', 'ebook', 'online', 'webtoon'])],
            'ebook_source' => ['nullable', 'required_if:format,ebook', Rule::in(['bookwalker', 'amazon', 'local_epub'])],
            'language' => ['nullable', Rule::in(['id', 'en', 'ja', 'other'])],
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
                'ebook_source' => $request->format === 'ebook' ? $request->ebook_source : null,
                'language' => $request->language,
            ]);
            $created++;
        }

        $message = $created > 0
            ? "{$created} volume berhasil ditambahkan".($skipped > 0 ? ", {$skipped} dilewati (sudah ada)." : '.')
            : 'Semua volume yang diinput sudah ada di koleksimu.';

        if ($created > 0) {
            ActivityLog::record(
                'collection.volumes.add',
                auth()->user()->name." menambahkan {$created} volume ke \"{$collection->series->title_romaji}\".",
                $collection,
            );
        }

        return redirect()->back()->with($created > 0 ? 'success' : 'info', $message);
    }

    public function updateVolumeFormat(Request $request, Collection $collection, CollectionVolume $collectionVolume): RedirectResponse
    {
        $this->authorize('update', $collection);

        abort_if($collectionVolume->collection_id !== $collection->id, 403);

        $request->validate([
            'format' => ['required', Rule::in(['physical', 'ebook', 'online', 'webtoon'])],
            'ebook_source' => ['nullable', 'required_if:format,ebook', Rule::in(['bookwalker', 'amazon', 'local_epub'])],
            'language' => ['nullable', Rule::in(['id', 'en', 'ja', 'other'])],
        ]);

        $collectionVolume->update([
            'format' => $request->format,
            'ebook_source' => $request->format === 'ebook' ? $request->ebook_source : null,
            'language' => $request->language,
        ]);

        ActivityLog::record(
            'collection.volumes.update_format',
            auth()->user()->name." mengubah format volume {$collectionVolume->volume_number} menjadi {$request->format}.",
            $collectionVolume,
        );

        return redirect()->back()->with('success', "Format volume {$collectionVolume->volume_number} berhasil diperbarui.");
    }

    public function updateVolumesFormat(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $request->validate([
            'volume_ids' => ['required', 'array', 'min:1'],
            'volume_ids.*' => ['uuid'],
            'format' => ['required', Rule::in(['physical', 'ebook', 'online', 'webtoon'])],
            'ebook_source' => ['nullable', 'required_if:format,ebook', Rule::in(['bookwalker', 'amazon', 'local_epub'])],
            'language' => ['nullable', Rule::in(['id', 'en', 'ja', 'other'])],
        ]);

        $updated = $collection->collectionVolumes()
            ->whereIn('id', $request->volume_ids)
            ->update([
                'format' => $request->format,
                'ebook_source' => $request->format === 'ebook' ? $request->ebook_source : null,
                'language' => $request->language,
            ]);

        ActivityLog::record(
            'collection.volumes.bulk_update_format',
            auth()->user()->name." mengubah format {$updated} volume menjadi {$request->format}.",
            $collection,
        );

        return redirect()->back()->with('success', "{$updated} volume berhasil diubah formatnya.");
    }

    public function destroyVolume(Collection $collection, CollectionVolume $collectionVolume): RedirectResponse
    {
        $this->authorize('update', $collection);

        abort_if($collectionVolume->collection_id !== $collection->id, 403);

        $payload = $collectionVolume->only(['volume_number', 'format', 'ebook_source', 'language', 'read_at']);
        $volumeNumber = $collectionVolume->volume_number;
        $collectionVolume->delete();

        ActivityLog::record(
            'collection.volumes.remove',
            auth()->user()->name." menghapus volume {$volumeNumber} dari \"{$collection->series->title_romaji}\".",
            $collection,
        );

        return redirect()->back()->with([
            'success' => 'Volume berhasil dihapus dari koleksi.',
            // Catatan: riwayat pinjaman (Loan) volume ini tidak ikut dipulihkan oleh undo.
            'undo_url' => route('collection.volumes.restore', $collection->id),
            'undo_payload' => ['volumes' => [$payload]],
        ]);
    }

    public function destroyVolumes(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $request->validate([
            'volume_ids' => ['required', 'array', 'min:1'],
            'volume_ids.*' => ['uuid'],
        ]);

        $volumes = $collection->collectionVolumes()
            ->whereIn('id', $request->volume_ids)
            ->get(['volume_number', 'format', 'ebook_source', 'language', 'read_at'])
            ->map->only(['volume_number', 'format', 'ebook_source', 'language', 'read_at'])
            ->all();

        $deleted = $collection->collectionVolumes()
            ->whereIn('id', $request->volume_ids)
            ->delete();

        ActivityLog::record(
            'collection.volumes.bulk_remove',
            auth()->user()->name." menghapus {$deleted} volume dari \"{$collection->series->title_romaji}\".",
            $collection,
        );

        return redirect()->back()->with([
            'success' => "{$deleted} volume berhasil dihapus dari koleksi.",
            // Catatan: riwayat pinjaman (Loan) volume-volume ini tidak ikut dipulihkan oleh undo.
            'undo_url' => route('collection.volumes.restore', $collection->id),
            'undo_payload' => ['volumes' => $volumes],
        ]);
    }

    public function restoreVolumes(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $request->validate([
            'volumes' => ['required', 'array', 'min:1'],
        ]);

        $existing = $collection->collectionVolumes()->pluck('volume_number')->all();
        $restored = 0;

        foreach ($request->volumes as $volume) {
            if (in_array($volume['volume_number'], $existing, true)) {
                continue;
            }
            $collection->collectionVolumes()->create(array_filter($volume, fn ($value) => $value !== null));
            $restored++;
        }

        ActivityLog::record(
            'collection.volumes.undo_remove',
            auth()->user()->name." memulihkan {$restored} volume di \"{$collection->series->title_romaji}\".",
            $collection,
        );

        return redirect()->back()->with('success', "{$restored} volume berhasil dipulihkan.");
    }

    public function toggleVolumeRead(Collection $collection, CollectionVolume $collectionVolume): RedirectResponse
    {
        $this->authorize('update', $collection);

        abort_if($collectionVolume->collection_id !== $collection->id, 403);

        $nowRead = ! $collectionVolume->read_at;

        $collectionVolume->update([
            'read_at' => $nowRead ? now() : null,
        ]);

        ActivityLog::record(
            'collection.volumes.toggle_read',
            auth()->user()->name.' menandai volume '.$collectionVolume->volume_number.($nowRead ? ' sudah dibaca.' : ' belum dibaca.'),
            $collectionVolume,
        );

        return redirect()->back()->with([
            'success' => $nowRead
                ? "Volume {$collectionVolume->volume_number} ditandai sudah dibaca."
                : "Volume {$collectionVolume->volume_number} ditandai belum dibaca.",
            'undo_url' => route('collection.volumes.toggleRead', [
                'collection' => $collection->id,
                'collectionVolume' => $collectionVolume->id,
            ]),
        ]);
    }

    public function markAllVolumesRead(Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $volumeIds = $collection->collectionVolumes()->whereNull('read_at')->pluck('id');

        if ($volumeIds->isEmpty()) {
            return redirect()->back()->with('info', 'Semua volume sudah ditandai dibaca.');
        }

        $collection->collectionVolumes()->whereIn('id', $volumeIds)->update(['read_at' => now()]);

        ActivityLog::record(
            'collection.volumes.mark_all_read',
            auth()->user()->name." menandai {$volumeIds->count()} volume di \"{$collection->series->title_romaji}\" sudah dibaca.",
            $collection,
        );

        return redirect()->back()->with([
            'success' => "{$volumeIds->count()} volume ditandai sudah dibaca.",
            'undo_url' => route('collection.volumes.unmarkRead', $collection->id),
            'undo_payload' => ['volume_ids' => $volumeIds->values()],
        ]);
    }

    public function unmarkVolumesRead(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $request->validate([
            'volume_ids' => ['required', 'array', 'min:1'],
            'volume_ids.*' => ['uuid'],
        ]);

        $collection->collectionVolumes()
            ->whereIn('id', $request->volume_ids)
            ->update(['read_at' => null]);

        ActivityLog::record('collection.volumes.undo_mark_read', auth()->user()->name.' membatalkan tanda baca volume.', $collection);

        return redirect()->back()->with('success', 'Perubahan dibatalkan.');
    }

    public function updateReview(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $request->validate([
            'personal_rating' => ['nullable', 'integer', 'min:-10', 'max:10'],
            'personal_review' => ['nullable', 'string', 'max:2000'],
        ]);

        $collection->update([
            'personal_rating' => $request->personal_rating,
            'personal_review' => $request->personal_review,
        ]);

        ActivityLog::record(
            'collection.update_review',
            auth()->user()->name." memperbarui review/rating untuk \"{$collection->series->title_romaji}\".",
            $collection,
        );

        return redirect()->back()->with('success', 'Review berhasil disimpan.');
    }
}
