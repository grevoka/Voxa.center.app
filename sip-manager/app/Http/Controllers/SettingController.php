<?php

namespace App\Http\Controllers;

use App\Models\SipSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = SipSetting::all()->groupBy('group');
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $fields = $request->validate([
            'sip_server'    => 'required|string|max:255',
            'sip_port'      => 'required|integer|min:1|max:65535',
            'sip_tls_port'  => 'required|integer|min:1|max:65535',
            'sip_transport'  => 'required|in:UDP,TCP,TLS',
            'max_auth_attempts' => 'required|integer|min:1|max:20',
            'ban_duration'   => 'required|integer|min:30|max:86400',
            'srtp_enabled'   => 'boolean',
            'tls_required'   => 'boolean',
        ]);

        foreach ($fields as $key => $value) {
            $type = is_bool($value) ? 'bool' : (is_int($value) ? 'int' : 'string');
            $group = in_array($key, ['max_auth_attempts', 'ban_duration', 'srtp_enabled', 'tls_required'])
                ? 'security' : 'general';

            SipSetting::set($key, $value, $group, $type);
        }

        ActivityLog::log('Paramètres sauvegardés', 'Configuration SIP mise à jour', 'info');

        return back()->with('success', 'Paramètres sauvegardés.');
    }
}
