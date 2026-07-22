<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Models\Series;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(): Response
    {
        $tickets = auth()->user()
            ->tickets()
            ->with('series:id,title_romaji')
            ->latest()
            ->paginate(15)
            ->through(fn ($t) => [
                'id'         => $t->id,
                'subject'    => $t->subject,
                'type'       => $t->type,
                'status'     => $t->status,
                'series'     => $t->series ? ['id' => $t->series->id, 'title_romaji' => $t->series->title_romaji] : null,
                'created_at' => $t->created_at->toDateString(),
            ]);

        return Inertia::render('User/Tickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Ticket::class);

        $series = null;

        if ($request->filled('series_id')) {
            $series = Series::query()
                ->select(['id', 'title_romaji', 'cover_path'])
                ->find($request->series_id);
        }

        return Inertia::render('User/Tickets/Create', [
            'series' => $series ? [
                'id'           => $series->id,
                'title_romaji' => $series->title_romaji,
                'cover_url'    => $series->cover_path ? Storage::url($series->cover_path) : null,
            ] : null,
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        auth()->user()->tickets()->create($request->validated());

        return redirect()->route('tickets.index')
            ->with('success', 'Tiket berhasil dikirim. Admin akan segera meninjau.');
    }

    public function show(Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        return Inertia::render('User/Tickets/Show', [
            'ticket' => [
                'id'             => $ticket->id,
                'subject'        => $ticket->subject,
                'type'           => $ticket->type,
                'message'        => $ticket->message,
                'status'         => $ticket->status,
                'admin_response' => $ticket->admin_response,
                'responded_at'   => $ticket->responded_at?->toDateTimeString(),
                'created_at'     => $ticket->created_at->toDateTimeString(),
                'series'         => $ticket->series ? [
                    'id'           => $ticket->series->id,
                    'title_romaji' => $ticket->series->title_romaji,
                ] : null,
            ],
        ]);
    }
}
