<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AsteriskLogController extends Controller
{
    public function index()
    {
        $logFile = '/var/log/asterisk/full';
        $lines = [];
        $fileExists = file_exists($logFile);

        if ($fileExists) {
            // Lire les 200 dernieres lignes
            $output = [];
            exec("tail -200 " . escapeshellarg($logFile), $output);
            $lines = $output;
        }

        return view('asterisk.logs', compact('lines', 'fileExists'));
    }

    /**
     * API endpoint for AJAX refresh.
     */
    public function tail(Request $request)
    {
        $logFile = '/var/log/asterisk/full';
        $n = min((int) $request->input('lines', 100), 500);

        if (!file_exists($logFile)) {
            return response()->json(['lines' => [], 'exists' => false]);
        }

        $output = [];
        exec("tail -{$n} " . escapeshellarg($logFile), $output);

        return response()->json(['lines' => $output, 'exists' => true]);
    }

    /**
     * Show Asterisk CLI output for a given command.
     */
    public function command(Request $request)
    {
        $allowed = [
            'pjsip show endpoints',
            'pjsip show registrations',
            'pjsip show contacts',
            'core show channels',
            'core show calls',
            'core show uptime',
            'module show like res_pjsip',
            'odbc show all',
            'database show',
        ];

        $cmd = $request->input('cmd', 'core show uptime');

        if (!in_array($cmd, $allowed)) {
            return response()->json(['error' => 'Commande non autorisee', 'output' => '']);
        }

        $output = [];
        exec("sudo /usr/sbin/asterisk -rx " . escapeshellarg($cmd) . " 2>&1", $output);

        return response()->json(['output' => implode("\n", $output), 'cmd' => $cmd]);
    }
}
