<?php

namespace App\Http\Controllers;

use App\Models\SipLine;
use App\Models\Trunk;
use App\Models\CallLog;
use App\Services\AsteriskAmiService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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

        $activeCalls = $ami->getActiveCalls();

        // Call stats for last 7 days (MRTG-style chart data)
        $chartData = $this->getCallChartData(7);

        // Today's call summary
        $today = Carbon::today();
        $todayStats = [
            'total'    => CallLog::whereDate('started_at', $today)->count(),
            'answered' => CallLog::whereDate('started_at', $today)->where('disposition', 'ANSWERED')->count(),
            'missed'   => CallLog::whereDate('started_at', $today)->where('disposition', 'NO ANSWER')->count(),
            'inbound'  => CallLog::whereDate('started_at', $today)->where('direction', 'inbound')->count(),
            'outbound' => CallLog::whereDate('started_at', $today)->where('direction', 'outbound')->count(),
        ];

        // Recent calls
        $recentCalls = CallLog::latest('started_at')->take(15)->get();

        return view('dashboard.index', compact('stats', 'activeCalls', 'chartData', 'todayStats', 'recentCalls'));
    }

    private function getCallChartData(int $days): array
    {
        $labels = [];
        $inbound = [];
        $outbound = [];
        $missed = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->format('d/m');

            $inbound[] = CallLog::whereDate('started_at', $date)
                ->where('direction', 'inbound')
                ->where('disposition', 'ANSWERED')
                ->count();

            $outbound[] = CallLog::whereDate('started_at', $date)
                ->where('direction', 'outbound')
                ->where('disposition', 'ANSWERED')
                ->count();

            $missed[] = CallLog::whereDate('started_at', $date)
                ->where('disposition', 'NO ANSWER')
                ->count();
        }

        return compact('labels', 'inbound', 'outbound', 'missed');
    }
}
