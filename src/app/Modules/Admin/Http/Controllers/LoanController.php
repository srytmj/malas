<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Collection\Models\Loan;
use App\Modules\Collection\Models\LoanEvent;
use App\Modules\Collection\Models\LoanItem;
use App\Modules\Collection\Models\UserCollection;
use App\Modules\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $loans = Loan::with(['user', 'items'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('borrower_name', 'like', "%{$request->search}%")
                ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%")))
            ->latest()->paginate(25);

        return view('admin.loans.index', compact('loans'));
    }

    public function create(Request $request)
    {
        $users = User::where('role', 'user')->orderBy('name')->get();
        $availableCollections = collect();
        $selectedUser = null;

        if ($request->user_id) {
            $selectedUser = User::find($request->user_id);
            if ($selectedUser) {
                $availableCollections = UserCollection::with(['userLibrary.series', 'volume'])
                    ->whereHas('userLibrary', fn($q) => $q->where('user_id', $request->user_id))
                    ->where('is_for_loan', true)
                    ->whereDoesntHave('loanItems', fn($q) => $q->whereNull('returned_at')
                        ->whereHas('loan', fn($l) => $l->whereIn('status', ['active', 'overdue'])))
                    ->get();
            }
        }

        return view('admin.loans.create', compact('users', 'availableCollections', 'selectedUser'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'          => 'required|exists:users,id',
            'borrower_name'    => 'required|string|max:255',
            'borrower_contact' => 'nullable|string|max:255',
            'loan_date'        => 'required|date',
            'due_date'         => 'nullable|date|after_or_equal:loan_date',
            'notes'            => 'nullable|string',
            'collection_ids'   => 'required|array|min:1',
            'collection_ids.*' => 'exists:user_collections,id',
        ]);

        $loan = Loan::create([
            'user_id'          => $request->user_id,
            'borrower_name'    => $request->borrower_name,
            'borrower_contact' => $request->borrower_contact,
            'loan_date'        => $request->loan_date,
            'due_date'         => $request->due_date,
            'notes'            => $request->notes,
            'status'           => 'active',
        ]);

        foreach ($request->collection_ids as $collectionId) {
            LoanItem::create([
                'loan_id'            => $loan->id,
                'user_collection_id' => $collectionId,
            ]);
        }

        LoanEvent::create([
            'loan_id'    => $loan->id,
            'event_type' => 'created',
            'metadata'   => ['note' => 'Dibuat oleh admin'],
        ]);

        return redirect()->route('admin.loans.show', $loan)->with('success', 'Loan berhasil dibuat.');
    }

    public function show(Loan $loan)
    {
        $loan->load(['user', 'items.userCollection.volume.series', 'events']);
        return view('admin.loans.show', compact('loan'));
    }

    public function edit(Loan $loan)
    {
        $loan->load(['user', 'items.userCollection.volume.series']);
        return view('admin.loans.edit', compact('loan'));
    }

    public function update(Request $request, Loan $loan)
    {
        $request->validate([
            'borrower_name'    => 'required|string|max:255',
            'borrower_contact' => 'nullable|string|max:255',
            'due_date'         => 'nullable|date',
            'notes'            => 'nullable|string',
        ]);

        $loan->update([
            'borrower_name'    => $request->borrower_name,
            'borrower_contact' => $request->borrower_contact,
            'due_date'         => $request->due_date,
            'notes'            => $request->notes,
        ]);

        LoanEvent::create([
            'loan_id'    => $loan->id,
            'event_type' => 'extended',
            'metadata'   => ['note' => 'Diupdate oleh admin'],
        ]);

        return redirect()->route('admin.loans.show', $loan)->with('success', 'Loan berhasil diupdate.');
    }

    public function markReturned(Loan $loan)
    {
        if ($loan->status === 'returned') {
            return back()->with('error', 'Loan sudah returned.');
        }

        $loan->update(['status' => 'returned', 'return_date' => now()->toDateString()]);
        $loan->items()->whereNull('returned_at')->update(['returned_at' => now()]);

        LoanEvent::create([
            'loan_id'    => $loan->id,
            'event_type' => 'returned',
            'metadata'   => ['note' => 'Ditandai returned oleh admin'],
        ]);

        return back()->with('success', 'Loan berhasil ditandai returned.');
    }

    public function markLost(Loan $loan)
    {
        $loan->update(['status' => 'lost']);

        LoanEvent::create([
            'loan_id'    => $loan->id,
            'event_type' => 'lost',
            'metadata'   => ['note' => 'Ditandai lost oleh admin'],
        ]);

        return back()->with('success', 'Loan berhasil ditandai lost.');
    }

    public function destroy(Request $request, Loan $loan)
    {
        $request->validate(['reason' => 'required|string|min:5']);
        $loan->deleteWithReason($request->reason);
        return redirect()->route('admin.loans.index')->with('success', 'Loan berhasil dihapus.');
    }
}
