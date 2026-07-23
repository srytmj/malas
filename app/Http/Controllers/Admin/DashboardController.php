<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Loan;
use App\Models\Series;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $stats = [
            'series_count' => Series::count(),
            'volumes_count' => Volume::count(),
            'collections_count' => Collection::count(),
            'users_count' => User::where('role', 'user')->count(),
            'active_loans_count' => Loan::whereNull('returned_at')->count(),
        ];

        $seriesByStatus = Series::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'series_by_status' => $seriesByStatus,
        ]);
    }
}
