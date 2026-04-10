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

        // Today's average duration
        $todayStats['avg_duration'] = (int) CallLog::whereDate('started_at', $today)
            ->where('disposition', 'ANSWERED')
            ->where('duration', '>', 0)
            ->avg('duration') ?: 0;
        $todayStats['total_duration'] = (int) CallLog::whereDate('started_at', $today)
            ->where('disposition', 'ANSWERED')
            ->sum('duration');

        // Missed calls per extension (today)
        $missedByExt = $this->getMissedByExtension($today);

        // Duration per extension (today)
        $durationByExt = $this->getDurationByExtension($today);

        // Recent calls
        $recentCalls = CallLog::latest('started_at')->take(15)->get();

        return view('dashboard.index', compact('stats', 'activeCalls', 'chartData', 'todayStats', 'recentCalls', 'missedByExt', 'durationByExt'));
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

    /**
     * Get internal extensions from SIP lines (operators only).
     */
    private function getInternalExtensions(): array
    {
        return SipLine::pluck('extension')->toArray();
    }

    private function getMissedByExtension(Carbon $date): array
    {
        $extensions = $this->getInternalExtensions();
        if (empty($extensions)) return [];

        // Build LIKE conditions for each internal extension
        $results = [];
        foreach ($extensions as $ext) {
            $missed = DB::connection('asterisk')
                ->table('cdr')
                ->whereDate('calldate', $date)
                ->where('disposition', 'NO ANSWER')
                ->where(function ($q) use ($ext) {
                    $q->where('dstchannel', 'like', "PJSIP/{$ext}-%")
                      ->orWhere('channel', 'like', "PJSIP/{$ext}-%");
                })
                ->count();

            if ($missed > 0) {
                $name = SipLine::where('extension', $ext)->value('name');
                $results[] = ['ext' => $ext, 'name' => $name ?? $ext, 'missed' => $missed];
            }
        }

        usort($results, fn ($a, $b) => $b['missed'] - $a['missed']);
        return array_slice($results, 0, 10);
    }

    private function getDurationByExtension(Carbon $date): array
    {
        $extensions = $this->getInternalExtensions();
        if (empty($extensions)) return [];

        $results = [];
        foreach ($extensions as $ext) {
            $row = DB::connection('asterisk')
                ->table('cdr')
                ->select(DB::raw('COUNT(*) as calls, SUM(billsec) as total_sec, ROUND(AVG(billsec)) as avg_sec'))
                ->whereDate('calldate', $date)
                ->where('disposition', 'ANSWERED')
                ->where('billsec', '>', 0)
                ->where(function ($q) use ($ext) {
                    $q->where('channel', 'like', "PJSIP/{$ext}-%")
                      ->orWhere('dstchannel', 'like', "PJSIP/{$ext}-%");
                })
                ->first();

            if ($row && $row->calls > 0) {
                $name = SipLine::where('extension', $ext)->value('name');
                $results[] = [
                    'ext' => $ext,
                    'name' => $name ?? $ext,
                    'calls' => $row->calls,
                    'total_sec' => $row->total_sec,
                    'avg_sec' => $row->avg_sec,
                ];
            }
        }

        usort($results, fn ($a, $b) => $b['total_sec'] - $a['total_sec']);
        return array_slice($results, 0, 10);
    }
}
