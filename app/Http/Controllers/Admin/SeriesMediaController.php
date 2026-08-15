<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Models\SeriesMedia;
use App\Services\StorageSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SeriesMediaController extends Controller
{
    public function __construct(private StorageSettingsService $storage) {}

    public function store(Request $request, Series $series): RedirectResponse
    {
        $this->authorize('update', $series);

        $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $nextOrder = (int) $series->media()->max('sort_order') + 1;

        foreach ($request->file('images') as $i => $file) {
            $path = $this->storage->storeUploadedFile($file, 'series-media');
            $series->media()->create([
                'image_path' => $path,
                'sort_order' => $nextOrder + $i,
            ]);
        }

        return redirect()->back()->with('success', __('flash.series_media.added'));
    }

    public function destroy(SeriesMedia $seriesMedia): RedirectResponse
    {
        $this->authorize('update', $seriesMedia->series);

        // Sengaja tidak ada undo di sini: file-nya langsung dihapus permanen dari storage,
        // jadi tidak ada apapun untuk dipulihkan tanpa infrastruktur deferred-delete.
        $this->storage->delete($seriesMedia->image_path);
        $seriesMedia->delete();

        return redirect()->back()->with('success', __('flash.series_media.deleted'));
    }
}
