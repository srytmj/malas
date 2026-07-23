<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Loan::class);

        $loans = Loan::with(['collection.user', 'collection.series', 'collectionVolume'])
            ->latest('loaned_at')
            ->paginate(20)
            ->through(fn ($l) => [
                'id' => $l->id,
                'user_name' => $l->collection->user->name,
                'series_title' => $l->collection->series->title_romaji,
                'volume_number' => $l->collectionVolume?->volume_number,
                'borrower_name' => $l->borrower_name,
                'loaned_at' => $l->loaned_at?->toDateString(),
                'due_at' => $l->due_at?->toDateString(),
                'returned_at' => $l->returned_at?->toDateString(),
                'is_overdue' => $l->isOverdue(),
            ]);

        return Inertia::render('Admin/Loans/Index', [
            'loans' => $loans,
        ]);
    }
}
