<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ImportJob;
use App\Models\TreatmentHistory;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', [
            'customerCount'  => Customer::count(),
            'latestImport'   => ImportJob::latest()->first(),
            'todayCount'     => TreatmentHistory::whereDate('treated_at', today())->count(),
            'week7Count'     => TreatmentHistory::where('treated_at', '>=', now()->subDays(7))->count(),
            'monthCount'     => TreatmentHistory::whereYear('treated_at', now()->year)
                                    ->whereMonth('treated_at', now()->month)->count(),
            'recentHistories'=> TreatmentHistory::with('customer')
                                    ->orderByDesc('treated_at')
                                    ->limit(5)
                                    ->get(),
        ]);
    }
}
