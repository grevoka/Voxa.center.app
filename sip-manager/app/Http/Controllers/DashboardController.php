<?php

namespace App\Http\Controllers;

use App\Models\SipLine;
use App\Models\Trunk;
use App\Models\ActivityLog;
use App\Services\AsteriskAmiService;

class DashboardController extends Controller
{
    public function index(AsteriskAmiService $ami)
    {
        $stats = [
            'lines_total'   => SipLine::count(),
            'lines_online'  => SipLine::where('status', 'online')->count(),
            'trunks_total'  => Trunk::count(),
            'trunks_online' => Trunk::where('status', 'online')->count(),
            'active_calls'  => count($ami->getActiveCalls()),
            'errors'        => Trunk::where('status', 'error')->count(),
        ];

        $activities = ActivityLog::with('user')
            ->latest()
            ->take(20)
            ->get();

        $activeCalls = $ami->getActiveCalls();

        return view('dashboard.index', compact('stats', 'activities', 'activeCalls'));
    }
}
