<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;

class AnnouncementController extends Controller
{
    public function dismiss(Announcement $announcement): RedirectResponse
    {
        $user = auth()->user();

        abort_unless($announcement->is_active, 403);

        if (! $user->dismissedAnnouncements()->where('announcement_id', $announcement->id)->exists()) {
            $user->dismissedAnnouncements()->attach($announcement->id, [
                'dismissed_at' => now(),
            ]);
        }

        return redirect()->back();
    }
}
