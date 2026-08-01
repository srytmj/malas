<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->isAdmin(), 403);

        $search = trim((string) $request->get('search', ''));
        $category = $request->get('category');
        $userId = $request->get('user_id');

        $logs = ActivityLog::with('user:id,name,avatar')
            ->when($search !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('description', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
            ))
            ->when($category, fn ($q) => $q->where('action', 'like', "{$category}.%"))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->latest('created_at')
            ->paginate($this->perPage())
            ->withQueryString()
            ->through(fn ($log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'user_name' => $log->user?->name ?? 'Sistem',
                'user_avatar' => $log->user?->avatar,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        $categories = ActivityLog::query()
            ->distinct()
            ->pluck('action')
            ->map(fn ($action) => str($action)->before('.')->toString())
            ->unique()
            ->sort()
            ->values();

        $users = User::query()
            ->whereHas('activityLogs')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/ActivityLog/Index', [
            'logs' => $logs,
            'categories' => $categories,
            'users' => $users,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'user_id' => $userId,
            ],
        ]);
    }
}
