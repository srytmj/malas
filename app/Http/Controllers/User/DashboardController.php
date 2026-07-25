<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $collectionIds = $user->collections()->pluck('id');

        $ownedCount = DB::table('collection_volumes')
            ->whereIn('collection_id', $collectionIds)
            ->count();

        $activeLoansCount = Loan::whereIn('collection_id', $collectionIds)
            ->whereNull('returned_at')
            ->count();

        $overdueCount = Loan::whereIn('collection_id', $collectionIds)
            ->whereNull('returned_at')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $latestTicket = Ticket::where('user_id', $user->id)
            ->latest()
            ->first(['id', 'subject', 'status']);

        return Inertia::render('User/Dashboard', [
            'stats' => [
                'series_count' => $collectionIds->count(),
                'owned_volumes_count' => $ownedCount,
                'active_loans_count' => $activeLoansCount,
                'overdue_count' => $overdueCount,
            ],
            'latest_ticket' => $latestTicket ? [
                'id' => $latestTicket->id,
                'subject' => $latestTicket->subject,
                'status' => $latestTicket->status,
            ] : null,
        ]);
    }
}
