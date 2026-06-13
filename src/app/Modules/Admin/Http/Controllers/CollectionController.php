<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Collection\Models\UserCollection;
use App\Modules\Collection\Models\UserLibrary;
use App\Modules\Collection\Models\Volume;
use App\Modules\Core\Models\Series;
use App\Modules\Core\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $collections = UserCollection::withTrashed()
            ->with(['userLibrary.user', 'userLibrary.series', 'volume'])
            ->when($request->search, fn($q) => $q
                ->whereHas('userLibrary.user', fn($u) => $u->where('name', 'like', "%{$request->search}%"))
                ->orWhereHas('userLibrary.series', fn($s) => $s->where('title_romaji', 'like', "%{$request->search}%")))
            ->latest()->paginate(25);

        return view('admin.collections.index', compact('collections'));
    }

    public function create(Request $request)
    {
        $users   = User::orderBy('name')->get();
        $series  = Series::orderBy('title_romaji')->get();
        $volumes = collect();

        $selectedUser   = null;
        $selectedSeries = null;

        if ($request->user_id) {
            $selectedUser = User::find($request->user_id);
        }
        if ($request->series_id) {
            $selectedSeries = Series::find($request->series_id);
            if ($selectedSeries) {
                // Exclude volumes the user already owns
                $ownedVolumeIds = [];
                if ($selectedUser) {
                    $library = UserLibrary::where('user_id', $request->user_id)
                        ->where('series_id', $request->series_id)
                        ->first();
                    if ($library) {
                        $ownedVolumeIds = $library->collections()->pluck('volume_id')->toArray();
                    }
                }

                $volumes = $selectedSeries->volumes()
                    ->orderBy('volume_number')
                    ->when($ownedVolumeIds, fn($q) => $q->whereNotIn('id', $ownedVolumeIds))
                    ->get();
            }
        }

        return view('admin.collections.create', compact('users', 'series', 'volumes', 'selectedUser', 'selectedSeries'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'        => 'required|exists:users,id',
            'series_id'      => 'required|exists:series,id',
            'volume_ids'     => 'required|array|min:1',
            'volume_ids.*'   => 'exists:volumes,id',
            'condition'      => 'required|in:mint,very_good,good,fair,poor',
            'is_for_loan'    => 'boolean',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date'  => 'nullable|date',
            'notes'          => 'nullable|string',
        ]);

        // Get or create user_library entry
        $library = UserLibrary::firstOrCreate([
            'user_id'   => $request->user_id,
            'series_id' => $request->series_id,
        ]);

        $created = 0;
        foreach ($request->volume_ids as $volumeId) {
            // Skip if already owned
            if ($library->collections()->where('volume_id', $volumeId)->exists()) {
                continue;
            }
            $library->collections()->create([
                'volume_id'      => $volumeId,
                'condition'      => $request->condition,
                'is_for_loan'    => $request->boolean('is_for_loan'),
                'purchase_price' => $request->purchase_price,
                'purchase_date'  => $request->purchase_date,
                'notes'          => $request->notes,
            ]);
            $created++;
        }

        return redirect()->route('admin.collections.index')
            ->with('success', "{$created} volume koleksi berhasil ditambahkan.");
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'                    => 'required|exists:users,id',
            'entries'                    => 'required|array|min:1',
            'entries.*.series_id'        => 'required|exists:series,id',
            'entries.*.volume_ids'       => 'required|array|min:1',
            'entries.*.volume_ids.*'     => 'exists:volumes,id',
            'entries.*.condition'        => 'required|in:mint,very_good,good,fair,poor',
            'entries.*.is_for_loan'      => 'boolean',
            'entries.*.purchase_price'   => 'nullable|numeric|min:0',
            'entries.*.purchase_date'    => 'nullable|date',
            'entries.*.notes'            => 'nullable|string',
        ]);

        $total = 0;
        foreach ($request->entries as $entry) {
            $library = UserLibrary::firstOrCreate([
                'user_id'   => $request->user_id,
                'series_id' => $entry['series_id'],
            ]);
            foreach ($entry['volume_ids'] as $volumeId) {
                if ($library->collections()->where('volume_id', $volumeId)->exists()) continue;
                $library->collections()->create([
                    'volume_id'      => $volumeId,
                    'condition'      => $entry['condition'],
                    'is_for_loan'    => (bool) ($entry['is_for_loan'] ?? true),
                    'purchase_price' => $entry['purchase_price'] ?? null,
                    'purchase_date'  => $entry['purchase_date'] ?? null,
                    'notes'          => $entry['notes'] ?? null,
                ]);
                $total++;
            }
        }

        return response()->json([
            'message' => "{$total} volume koleksi berhasil ditambahkan.",
            'count'   => $total,
        ]);
    }

    public function show(UserCollection $collection)
    {
        $collection->load(['userLibrary.user', 'userLibrary.series', 'volume', 'loanItems.loan']);
        return view('admin.collections.show', compact('collection'));
    }

    public function edit(UserCollection $collection)
    {
        $collection->load(['userLibrary.user', 'userLibrary.series', 'volume']);
        return view('admin.collections.edit', compact('collection'));
    }

    public function update(Request $request, UserCollection $collection)
    {
        $request->validate([
            'condition'      => 'required|in:mint,very_good,good,fair,poor',
            'is_for_loan'    => 'boolean',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date'  => 'nullable|date',
            'notes'          => 'nullable|string',
        ]);

        $collection->update([
            'condition'      => $request->condition,
            'is_for_loan'    => $request->boolean('is_for_loan'),
            'purchase_price' => $request->purchase_price,
            'purchase_date'  => $request->purchase_date,
            'notes'          => $request->notes,
        ]);

        return redirect()->route('admin.collections.show', $collection)->with('success', 'Koleksi berhasil diupdate.');
    }

    public function destroy(Request $request, UserCollection $collection)
    {
        $request->validate(['reason' => 'required|string|min:5']);
        $collection->deleteWithReason($request->reason);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Koleksi berhasil dihapus.']);
        }

        return redirect()->route('admin.collections.index')->with('success', 'Koleksi berhasil dihapus.');
    }
}
