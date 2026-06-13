<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Modules\Collection\Models\Loan;
use App\Modules\Collection\Models\UserCollection;
use App\Modules\Collection\Models\UserLibrary;
use App\Modules\Core\Models\Series;
use App\Modules\Core\Models\User;
use Illuminate\Routing\Controller;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'users'        => User::count(),
            'banned'       => User::where('is_banned', true)->count(),
            'series'       => Series::count(),
            'libraries'    => UserLibrary::count(),
            'collections'  => UserCollection::count(),
            'active_loans' => Loan::whereIn('status', ['active', 'overdue'])->count(),
        ];

        $recentLoans = Loan::with('user')->latest()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'recentLoans'));
    }
}
