<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVolumeRequest;
use App\Http\Requests\Admin\UpdateVolumeRequest;
use App\Models\ActivityLog;
use App\Models\Series;
use App\Models\Volume;
use App\Services\StorageSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class VolumeController extends Controller
{
    public function __construct(private StorageSettingsService $storage) {}

    public function generate(Series $series): RedirectResponse
    {
        $this->authorize('create', Volume::class);

        if (! $series->total_volumes) {
            return redirect()->back()->with('error', __('flash.volumes.total_not_set'));
        }

        $existing = $series->volumes()->pluck('volume_number')->all();
        $created = 0;

        for ($i = 1; $i <= $series->total_volumes; $i++) {
            if (! in_array($i, $existing)) {
                $series->volumes()->create(['volume_number' => $i, 'type' => 'regular']);
                $created++;
            }
        }

        $message = $created > 0
            ? __('flash.volumes.generated', ['count' => $created])
            : __('flash.volumes.all_already_exist');

        return redirect()->back()->with($created > 0 ? 'success' : 'info', $message);
    }

    public function store(StoreVolumeRequest $request, Series $series): RedirectResponse
    {
        $this->authorize('create', Volume::class);

        $data = $request->validated();
        $data['series_id'] = $series->id;
        $data['cover_path'] = $request->hasFile('cover')
            ? $this->storage->storeUploadedFile($request->file('cover'), 'covers/volumes')
            : null;
        unset($data['cover']);

        $series->volumes()->create($data);

        return redirect()->back()
            ->with('success', __('flash.volumes.created'));
    }

    public function edit(Volume $volume): Response
    {
        $this->authorize('update', $volume);

        return Inertia::render('Admin/Series/EditVolume', [
            'volume' => [
                'id' => $volume->id,
                'series_id' => $volume->series_id,
                'volume_number' => $volume->volume_number,
                'type' => $volume->type,
                'isbn' => $volume->isbn,
                'published_at' => $volume->published_at?->toDateString(),
                'cover_url' => $this->storage->url($volume->cover_path),
            ],
            'series' => $volume->series->only(['id', 'slug', 'title_romaji']),
        ]);
    }

    public function update(UpdateVolumeRequest $request, Volume $volume): RedirectResponse
    {
        $this->authorize('update', $volume);

        $data = $request->validated();

        if ($request->hasFile('cover')) {
            if ($volume->cover_path) {
                $this->storage->delete($volume->cover_path);
            }
            $data['cover_path'] = $this->storage->storeUploadedFile($request->file('cover'), 'covers/volumes');
        } elseif ($request->filled('cover_url')) {
            $fetched = $this->fetchCoverFromUrl($request->cover_url);
            if ($fetched) {
                if ($volume->cover_path) {
                    $this->storage->delete($volume->cover_path);
                }
                $data['cover_path'] = $fetched;
            }
        }

        unset($data['cover'], $data['cover_url']);
        $volume->update($data);

        return redirect()->back()
            ->with('success', __('flash.volumes.updated'));
    }

    public function destroy(Volume $volume): RedirectResponse
    {
        $this->authorize('delete', $volume);

        $volumeId = $volume->id;
        $hadCover = (bool) $volume->cover_path;

        ActivityLog::record(
            'volume.delete',
            "Menghapus volume #{$volume->volume_number} dari series \"{$volume->series->title_romaji}\".",
            $volume
        );

        if ($volume->cover_path) {
            $this->storage->delete($volume->cover_path);
        }

        $volume->delete();

        return redirect()->back()->with([
            // Cover (kalau ada) sudah terhapus permanen dari storage — undo memulihkan datanya
            // tapi covernya perlu diupload ulang manual.
            'success' => __('flash.volumes.deleted').($hadCover ? __('flash.volumes.deleted_cover_note') : ''),
            'undo_url' => route('admin.volumes.restore', $volumeId),
        ]);
    }

    public function restore(string $volume): RedirectResponse
    {
        $volumeModel = Volume::withTrashed()->findOrFail($volume);

        $this->authorize('delete', $volumeModel);

        $volumeModel->restore();
        ActivityLog::record(
            'volume.restore',
            "Memulihkan volume #{$volumeModel->volume_number} dari series \"{$volumeModel->series->title_romaji}\"."
        );

        return redirect()->back()->with('success', __('flash.volumes.restored'));
    }

    private function fetchCoverFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(20)->get($url);
            if (! $response->successful()) {
                return null;
            }

            $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';

            return $this->storage->storeContents('covers/volumes', 'url_'.uniqid().'.'.$ext, $response->body());
        } catch (\Exception) {
            return null;
        }
    }
}
