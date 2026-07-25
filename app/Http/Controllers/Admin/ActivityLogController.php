<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->isAdmin(), 403);

        $logs = ActivityLog::with('user:id,name,avatar')
            ->latest('created_at')
            ->paginate(30)
            ->through(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user_name' => $log->user?->name ?? 'Sistem',
                'user_avatar' => $log->user?->avatar,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/ActivityLog/Index', [
            'logs' => $logs,
        ]);
    }
}
