<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RespondTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Ticket::class);

        $tickets = Ticket::query()
            ->with(['user:id,name', 'series:id,title_romaji'])
            ->when(request('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString()
            ->through(fn ($t) => [
                'id' => $t->id,
                'subject' => $t->subject,
                'type' => $t->type,
                'status' => $t->status,
                'user_name' => $t->user->name,
                'series' => $t->series ? ['id' => $t->series->id, 'title_romaji' => $t->series->title_romaji] : null,
                'created_at' => $t->created_at->toDateString(),
            ]);

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => request()->only(['status']),
        ]);
    }

    public function show(Ticket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load(['user:id,name,email', 'series:id,title_romaji', 'respondedBy:id,name']);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => [
                'id' => $ticket->id,
                'subject' => $ticket->subject,
                'type' => $ticket->type,
                'message' => $ticket->message,
                'status' => $ticket->status,
                'admin_response' => $ticket->admin_response,
                'responded_at' => $ticket->responded_at?->toDateTimeString(),
                'responded_by' => $ticket->respondedBy?->name,
                'created_at' => $ticket->created_at->toDateTimeString(),
                'user' => [
                    'name' => $ticket->user->name,
                    'email' => $ticket->user->email,
                ],
                'series' => $ticket->series ? [
                    'id' => $ticket->series->id,
                    'title_romaji' => $ticket->series->title_romaji,
                ] : null,
            ],
            'can' => [
                'respond' => request()->user()->can('respond', $ticket),
            ],
        ]);
    }

    public function respond(RespondTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('respond', $ticket);

        $ticket->update([
            'admin_response' => $request->admin_response,
            'status' => $request->status,
            'responded_by' => $request->user()->id,
            'responded_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Respons berhasil dikirim.');
    }
}
