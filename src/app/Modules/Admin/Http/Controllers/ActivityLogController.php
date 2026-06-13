<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Core\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->action, fn($q) => $q->where('action', 'like', "%{$request->action}%"))
            ->latest()->paginate(50);

        return view('admin.activity-log.index', compact('logs'));
    }
}
