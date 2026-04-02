<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use Illuminate\Http\Request;

class CallLogController extends Controller
{
    public function index(Request $request)
    {
        $query = CallLog::query()->latest('started_at');

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('src', 'like', "%{$s}%")
                  ->orWhere('dst', 'like', "%{$s}%")
                  ->orWhere('src_name', 'like', "%{$s}%")
                  ->orWhere('context', 'like', "%{$s}%");
            });
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        if ($request->filled('disposition')) {
            $query->where('disposition', $request->input('disposition'));
        }

        if ($request->filled('date_from')) {
            $query->where('started_at', '>=', $request->input('date_from') . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('started_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $logs = $query->paginate(50)->withQueryString();

        $stats = [
            'total'    => CallLog::count(),
            'answered' => CallLog::where('disposition', 'ANSWERED')->count(),
            'missed'   => CallLog::where('disposition', 'NO ANSWER')->count(),
            'failed'   => CallLog::whereIn('disposition', ['FAILED', 'CONGESTION'])->count(),
        ];

        return view('logs.index', compact('logs', 'stats'));
    }
}
