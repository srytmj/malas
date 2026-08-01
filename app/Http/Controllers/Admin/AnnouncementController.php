<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Announcement::class);

        $announcements = Announcement::latest()
            ->paginate($this->perPage())
            ->withQueryString()
            ->through(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'type' => $a->type,
                'is_active' => $a->is_active,
                'starts_at' => $a->starts_at?->toDateTimeString(),
                'expires_at' => $a->expires_at?->toDateTimeString(),
                'created_at' => $a->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Announcements/Index', [
            'items' => $announcements,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Announcement::class);

        return Inertia::render('Admin/Announcements/Create');
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $this->authorize('create', Announcement::class);

        Announcement::create($request->validated());

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil dibuat.');
    }

    public function edit(Announcement $announcement): Response
    {
        $this->authorize('update', $announcement);

        return Inertia::render('Admin/Announcements/Edit', [
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'type' => $announcement->type,
                'is_active' => $announcement->is_active,
                'starts_at' => $announcement->starts_at?->format('Y-m-d\TH:i'),
                'expires_at' => $announcement->expires_at?->format('Y-m-d\TH:i'),
            ],
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $announcement->update($request->validated());

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('delete', $announcement);

        $payload = $announcement->only(['title', 'body', 'type', 'is_active', 'starts_at', 'expires_at']);
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with([
            'success' => 'Pengumuman berhasil dihapus.',
            'undo_url' => route('admin.announcements.restore'),
            'undo_payload' => $payload,
        ]);
    }

    public function restore(Request $request): RedirectResponse
    {
        $this->authorize('create', Announcement::class);

        $request->validate([
            'title' => ['required', 'string'],
            'body' => ['required', 'string'],
            'type' => ['required', 'string'],
        ]);

        Announcement::create($request->only(['title', 'body', 'type', 'is_active', 'starts_at', 'expires_at']));

        return redirect()->back()->with('success', 'Pengumuman berhasil dipulihkan.');
    }
}
