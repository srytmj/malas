<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Core\Models\ActivityLog;
use App\Modules\Core\Models\Series;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

class SeriesController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->datatableResponse($request);
        }
        return view('admin.series.index');
    }

    private function datatableResponse(Request $request): JsonResponse
    {
        $query = Series::query();
        $total = Series::count();

        if ($search = $request->input('search.value')) {
            $query->where(fn($q) =>
                $q->where('title_romaji', 'like', "%{$search}%")
                  ->orWhere('title_english', 'like', "%{$search}%")
                  ->orWhere('mal_id', 'like', "%{$search}%")
            );
        }

        if ($status = $request->input('status_filter')) {
            $query->where('status', $status);
        }

        $filtered = $query->count();

        $colMap = [0 => 'title_romaji', 1 => 'status', 2 => 'total_volumes', 3 => 'score', 4 => 'last_synced_at'];
        $colIdx = (int) $request->input('order.0.column', 0);
        $dir    = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($colMap[$colIdx] ?? 'title_romaji', $dir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $data   = $query->offset($start)->limit($length)->get();

        $diskUrl = fn($path) => $path ? Storage::url($path) : null;

        return response()->json([
            'draw'            => (int) $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data->map(fn($s) => [
                'id'           => $s->id,
                'title_romaji' => $s->title_romaji,
                'title_english'=> $s->title_english,
                'mal_id'       => $s->mal_id,
                'status'       => $s->status,
                'total_volumes'=> $s->total_volumes,
                'score'        => $s->score,
                'cover_url'    => $diskUrl($s->cover_image_path),
                'show_url'     => route('admin.series.show', $s->id),
                'edit_url'     => route('admin.series.edit', $s->id),
                'delete_url'   => route('admin.series.destroy', $s->id),
                'delete_token' => csrf_token(),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.series.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title_romaji'   => 'required|string|max:255',
            'title_english'  => 'nullable|string|max:255',
            'title_japanese' => 'nullable|string|max:255',
            'synopsis'       => 'nullable|string',
            'status'         => 'required|in:publishing,finished,on_hiatus,discontinued,not_yet_published',
            'total_volumes'  => 'nullable|integer|min:1',
            'published_from' => 'nullable|date',
            'published_to'   => 'nullable|date|after_or_equal:published_from',
            'score'          => 'nullable|numeric|min:0|max:10',
            'rank'           => 'nullable|integer|min:1',
            'mal_id'         => 'nullable|integer|unique:series,mal_id',
            'cover'          => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('cover')) {
            $validated['cover_image_path'] = $request->file('cover')->store('covers/series', 'public');
        }
        unset($validated['cover']);

        $series = Series::create($validated);

        ActivityLog::create([
            'user_id' => auth()->id(), 'action' => 'series.created',
            'entity_type' => 'series', 'entity_id' => $series->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.series.show', $series)->with('success', 'Series berhasil ditambahkan.');
    }

    public function show(Series $series)
    {
        $series->load('volumes');
        $users = \App\Modules\Core\Models\User::orderBy('name')->get();
        return view('admin.series.show', compact('series', 'users'));
    }

    public function edit(Series $series)
    {
        return view('admin.series.edit', compact('series'));
    }

    public function update(Request $request, Series $series)
    {
        $validated = $request->validate([
            'title_romaji'   => 'required|string|max:255',
            'title_english'  => 'nullable|string|max:255',
            'title_japanese' => 'nullable|string|max:255',
            'synopsis'       => 'nullable|string',
            'status'         => 'required|in:publishing,finished,on_hiatus,discontinued,not_yet_published',
            'total_volumes'  => 'nullable|integer|min:1',
            'published_from' => 'nullable|date',
            'published_to'   => 'nullable|date|after_or_equal:published_from',
            'score'          => 'nullable|numeric|min:0|max:10',
            'rank'           => 'nullable|integer|min:1',
            'cover'          => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('cover')) {
            if ($series->cover_image_path) {
                Storage::disk('public')->delete($series->cover_image_path);
            }
            $validated['cover_image_path'] = $request->file('cover')->store('covers/series', 'public');
        }
        unset($validated['cover']);

        $series->update($validated);

        ActivityLog::create([
            'user_id' => auth()->id(), 'action' => 'series.updated',
            'entity_type' => 'series', 'entity_id' => $series->id,
            'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.series.show', $series)->with('success', 'Series berhasil diupdate.');
    }

    public function destroy(Request $request, Series $series)
    {
        $request->validate(['reason' => 'required|string|min:5']);
        $series->deleteWithReason($request->reason);

        ActivityLog::create([
            'user_id' => auth()->id(), 'action' => 'series.deleted',
            'entity_type' => 'series', 'entity_id' => $series->id,
            'reason' => $request->reason, 'ip_address' => request()->ip(),
        ]);

        return redirect()->route('admin.series.index')->with('success', 'Series berhasil dihapus.');
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $request->validate([
            'reason'        => 'required|string|min:5',
            'search'        => 'nullable|string',
            'status_filter' => 'nullable|string',
        ]);

        $query = Series::query();

        if ($search = $request->input('search')) {
            $query->where(fn($q) =>
                $q->where('title_romaji', 'like', "%{$search}%")
                  ->orWhere('title_english', 'like', "%{$search}%")
                  ->orWhere('mal_id', 'like', "%{$search}%")
            );
        }
        if ($status = $request->input('status_filter')) {
            $query->where('status', $status);
        }

        // Collect all IDs upfront to avoid offset drift when soft-deleting during iteration
        $ids    = $query->pluck('id');
        $reason = $request->reason;
        $userId = auth()->id();
        $ip     = request()->ip();
        $count  = 0;

        foreach ($ids as $id) {
            $series = Series::find($id);
            if (! $series) continue;
            $series->deleteWithReason($reason);
            ActivityLog::create([
                'user_id'     => $userId,
                'action'      => 'series.deleted',
                'entity_type' => 'series',
                'entity_id'   => $id,
                'reason'      => $reason,
                'ip_address'  => $ip,
            ]);
            $count++;
        }

        return response()->json([
            'message' => "{$count} series berhasil dihapus.",
            'count'   => $count,
        ]);
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids'    => 'required|array|min:1|max:200',
            'ids.*'  => 'string',
            'reason' => 'required|string|min:5',
        ]);

        $count = 0;
        foreach ($request->ids as $id) {
            $series = Series::find($id);
            if ($series && ! $series->trashed()) {
                $series->deleteWithReason($request->reason);
                ActivityLog::create([
                    'user_id'     => auth()->id(),
                    'action'      => 'series.deleted',
                    'entity_type' => 'series',
                    'entity_id'   => $series->id,
                    'reason'      => $request->reason,
                    'ip_address'  => request()->ip(),
                ]);
                $count++;
            }
        }

        return response()->json([
            'message' => "{$count} series berhasil dihapus.",
            'count'   => $count,
        ]);
    }
}
