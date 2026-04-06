<?php

namespace App\Http\Controllers;

use App\Models\SipSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

    public function updateSmtp(Request $request)
    {
        $fields = $request->validate([
            'smtp_host'       => 'required|string|max:255',
            'smtp_port'       => 'required|integer|min:1|max:65535',
            'smtp_username'   => 'nullable|string|max:255',
            'smtp_password'   => 'nullable|string|max:255',
            'smtp_encryption' => 'required|in:none,tls,ssl',
            'smtp_from_address' => 'required|email|max:255',
            'smtp_from_name'  => 'required|string|max:255',
            'voicemail_notify_enabled' => 'boolean',
        ]);

        foreach ($fields as $key => $value) {
            $type = is_bool($value) ? 'bool' : 'string';
            SipSetting::set($key, $value ?? '', 'smtp', $type);
        }

        // Ensure checkbox default
        if (!$request->has('voicemail_notify_enabled')) {
            SipSetting::set('voicemail_notify_enabled', false, 'smtp', 'bool');
        }

        ActivityLog::log('SMTP configuré', 'Paramètres SMTP mis à jour', 'info');

        return back()->with('success', 'Configuration SMTP sauvegardée.');
    }

    public function testSmtp(Request $request)
    {
        $to = $request->validate(['test_email' => 'required|email'])['test_email'];

        try {
            $this->applySmtpConfig();

            Mail::raw("Ceci est un email de test envoye depuis SIP.ctrl.\n\nSi vous recevez ce message, la configuration SMTP est correcte.", function ($message) use ($to) {
                $message->to($to)
                    ->subject('SIP.ctrl — Test SMTP');
            });

            return back()->with('success', "Email de test envoyé à {$to}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur SMTP: ' . $e->getMessage());
        }
    }

    private function applySmtpConfig(): void
    {
        $encryption = SipSetting::get('smtp_encryption', 'tls');

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => SipSetting::get('smtp_host', '127.0.0.1'),
            'mail.mailers.smtp.port' => (int) SipSetting::get('smtp_port', 587),
            'mail.mailers.smtp.username' => SipSetting::get('smtp_username', ''),
            'mail.mailers.smtp.password' => SipSetting::get('smtp_password', ''),
            'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
            'mail.from.address' => SipSetting::get('smtp_from_address', 'noreply@sipctrl.local'),
            'mail.from.name' => SipSetting::get('smtp_from_name', 'SIP.ctrl'),
        ]);
    }
}
