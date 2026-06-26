<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVolumeRequest;
use App\Http\Requests\Admin\UpdateVolumeRequest;
use App\Models\Series;
use App\Models\Volume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class VolumeController extends Controller
{
    public function store(StoreVolumeRequest $request, Series $series): RedirectResponse
    {
        $this->authorize('create', Volume::class);

        $data              = $request->validated();
        $data['series_id'] = $series->id;
        $data['cover_path'] = $request->hasFile('cover')
            ? $request->file('cover')->store('covers/volumes', 'public')
            : null;
        unset($data['cover']);

        $series->volumes()->create($data);

        return redirect()->route('admin.series.show', $series)
            ->with('success', 'Volume berhasil ditambahkan.');
    }

    public function edit(Volume $volume): Response
    {
        $this->authorize('update', $volume);

        return Inertia::render('Admin/Series/EditVolume', [
            'volume' => [
                'id'            => $volume->id,
                'series_id'     => $volume->series_id,
                'volume_number' => $volume->volume_number,
                'type'          => $volume->type,
                'isbn'          => $volume->isbn,
                'published_at'  => $volume->published_at?->toDateString(),
                'cover_url'     => $volume->cover_path ? Storage::url($volume->cover_path) : null,
            ],
            'series' => $volume->series->only(['id', 'title_romaji']),
        ]);
    }

    public function update(UpdateVolumeRequest $request, Volume $volume): RedirectResponse
    {
        $this->authorize('update', $volume);

        $data = $request->validated();

        if ($request->hasFile('cover')) {
            if ($volume->cover_path) {
                Storage::disk('public')->delete($volume->cover_path);
            }
            $data['cover_path'] = $request->file('cover')->store('covers/volumes', 'public');
        }

        unset($data['cover']);
        $volume->update($data);

        return redirect()->route('admin.series.show', $volume->series_id)
            ->with('success', 'Volume berhasil diperbarui.');
    }

    public function destroy(Volume $volume): RedirectResponse
    {
        $this->authorize('delete', $volume);

        $seriesId = $volume->series_id;

        if ($volume->cover_path) {
            Storage::disk('public')->delete($volume->cover_path);
        }

        $volume->delete();

        return redirect()->route('admin.series.show', $seriesId)
            ->with('success', 'Volume berhasil dihapus.');
    }
}
