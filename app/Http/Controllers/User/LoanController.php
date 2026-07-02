<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\CollectionVolume;
use App\Models\Loan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $collectionIds = $user->collections()->pluck('id');

        $loans = Loan::whereIn('collection_id', $collectionIds)
            ->with(['collection.series', 'collectionVolume'])
            ->latest('loaned_at')
            ->paginate(20)
            ->through(fn ($l) => [
                'id'            => $l->id,
                'collection_id' => $l->collection_id,
                'series_title'  => $l->collection->series->title_romaji,
                'volume_number' => $l->collectionVolume?->volume_number,
                'borrower_name' => $l->borrower_name,
                'loaned_at'     => $l->loaned_at?->toDateString(),
                'due_at'        => $l->due_at?->toDateString(),
                'returned_at'   => $l->returned_at?->toDateString(),
                'notes'         => $l->notes,
                'is_overdue'    => $l->isOverdue(),
            ]);

        return Inertia::render('User/Loans/Index', [
            'loans' => $loans,
        ]);
    }

    public function store(Request $request, Collection $collection): RedirectResponse
    {
        $this->authorize('update', $collection);

        $data = $request->validate([
            'collection_volume_id' => ['required', 'uuid', 'exists:collection_volumes,id'],
            'borrower_name'        => ['required', 'string', 'max:255'],
            'loaned_at'            => ['required', 'date'],
            'due_at'               => ['nullable', 'date', 'after:loaned_at'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ]);

        $cv = CollectionVolume::findOrFail($data['collection_volume_id']);
        abort_if($cv->collection_id !== $collection->id, 403, 'Volume tidak ada di koleksi ini.');

        $alreadyLoaned = $cv->activeLoans()->exists();

        if ($alreadyLoaned) {
            return redirect()->back()->with('error', 'Volume ini masih dalam status dipinjam.');
        }

        Loan::create([
            ...$data,
            'collection_id' => $collection->id,
        ]);

        return redirect()->back()->with('success', 'Pinjaman berhasil dicatat.');
    }

    public function markReturned(Loan $loan): RedirectResponse
    {
        $this->authorize('update', $loan);

        $loan->update(['returned_at' => now()->toDateString()]);

        return redirect()->back()->with('success', 'Volume berhasil ditandai sudah dikembalikan.');
    }
}
