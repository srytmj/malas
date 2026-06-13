<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Collection\Models\Volume;
use App\Modules\Core\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VolumeController extends Controller
{
    public function index(Series $series)
    {
        $volumes = $series->volumes()->orderBy('volume_number')->get();
        return view('admin.volumes.index', compact('series', 'volumes'));
    }

    public function store(Request $request, Series $series)
    {
        $request->validate([
            'volume_number' => [
                'required', 'numeric', 'min:0.5',
                function ($attr, $val, $fail) use ($series) {
                    if ($series->volumes()->where('volume_number', $val)->exists()) {
                        $fail("Volume {$val} sudah ada untuk series ini.");
                    }
                },
            ],
            'title'        => 'nullable|string|max:255',
            'isbn'         => 'nullable|string|max:50',
            'publisher'    => 'nullable|string|max:100',
            'release_date' => 'nullable|date',
        ]);

        Volume::create([
            'series_id'     => $series->id,
            'volume_number' => $request->volume_number,
            'title'         => $request->title,
            'isbn'          => $request->isbn,
            'publisher'     => $request->publisher,
            'release_date'  => $request->release_date,
        ]);

        return redirect()->route('admin.volumes.index', $series)
            ->with('success', "Volume {$request->volume_number} berhasil ditambahkan.");
    }

    public function edit(Series $series, Volume $volume)
    {
        return view('admin.volumes.edit', compact('series', 'volume'));
    }

    public function update(Request $request, Series $series, Volume $volume)
    {
        $request->validate([
            'volume_number' => [
                'required', 'numeric', 'min:0.5',
                function ($attr, $val, $fail) use ($series, $volume) {
                    if ($series->volumes()->where('volume_number', $val)->where('id', '!=', $volume->id)->exists()) {
                        $fail("Volume {$val} sudah ada untuk series ini.");
                    }
                },
            ],
            'title'        => 'nullable|string|max:255',
            'isbn'         => 'nullable|string|max:50',
            'publisher'    => 'nullable|string|max:100',
            'release_date' => 'nullable|date',
        ]);

        $volume->update([
            'volume_number' => $request->volume_number,
            'title'         => $request->title,
            'isbn'          => $request->isbn,
            'publisher'     => $request->publisher,
            'release_date'  => $request->release_date,
        ]);

        return redirect()->route('admin.volumes.index', $series)
            ->with('success', 'Volume berhasil diupdate.');
    }

    public function destroy(Series $series, Volume $volume)
    {
        $volume->delete();
        return redirect()->route('admin.volumes.index', $series)
            ->with('success', "Volume {$volume->volume_number} berhasil dihapus.");
    }
}
