<?php

use App\Http\Controllers\InstallController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SipLineController;
use App\Http\Controllers\TrunkController;
use App\Http\Controllers\CallContextController;
use App\Http\Controllers\CallLogController;
use App\Http\Controllers\AsteriskLogController;
use App\Http\Controllers\CallFlowController;
use App\Http\Controllers\CallQueueController;
use App\Http\Controllers\OutboundRouteController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\ConferenceRoomController;
use App\Http\Controllers\VoicemailController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\MohController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\FirewallController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\OperatorDashboardController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\ProfileController;
use App\Services\AsteriskAmiService;
use Illuminate\Support\Facades\Route;

// Installation wizard (no auth required)
Route::get('install', [InstallController::class, 'index'])->name('install.index');
Route::post('install/requirements', [InstallController::class, 'requirements'])->name('install.requirements');
Route::post('install/database', [InstallController::class, 'database'])->name('install.database');
Route::post('install/admin', [InstallController::class, 'admin'])->name('install.admin');
Route::post('install/finalize', [InstallController::class, 'finalize'])->name('install.finalize');

Route::middleware(['auth', 'verified'])->group(function () {

    // ── Root: redirect based on role ──
    Route::get('/', function () {
        if (auth()->user()->isOperator()) {
            return redirect()->route('operator.dashboard');
        }
        return app(DashboardController::class)->index(app(AsteriskAmiService::class));
    })->name('dashboard');

    // ── Shared routes (all roles) ──
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::view('help', 'help.index')->name('help.index');

    // ── Operator space ──
    Route::middleware('operator')->prefix('operator')->name('operator.')->group(function () {
        Route::get('/', [OperatorDashboardController::class, 'index'])->name('dashboard');
        Route::get('/calls', [OperatorDashboardController::class, 'calls'])->name('calls');
        Route::get('/voicemail', [OperatorDashboardController::class, 'voicemail'])->name('voicemail');
        Route::get('/voicemail/{folder}/{file}/play', [OperatorDashboardController::class, 'playVoicemail'])->name('voicemail.play');
        Route::delete('/voicemail/{folder}/{file}', [OperatorDashboardController::class, 'destroyVoicemail'])->name('voicemail.destroy');
        Route::get('/phone/config', [\App\Http\Controllers\WebPhoneController::class, 'config'])->name('phone.config');
    });

    // ── Impersonation ──
    Route::post('impersonate/stop', [ImpersonateController::class, 'stop'])->name('admin.impersonate.stop');
    Route::post('impersonate/{user}', [ImpersonateController::class, 'start'])->name('admin.impersonate')->middleware('admin');

    // ══════════════════════════════════════
    // ── Admin-only routes ──
    // ══════════════════════════════════════
    Route::middleware('admin')->group(function () {

        // Lignes SIP
        Route::resource('lines', SipLineController::class)->except(['show']);
        Route::post('lines/{line}/toggle', [SipLineController::class, 'toggle'])->name('lines.toggle');

        // Operateurs
        Route::resource('operators', OperatorController::class)->except(['show']);

        // Trunks
        Route::resource('trunks', TrunkController::class)->except(['show']);
        Route::post('trunks/{trunk}/toggle', [TrunkController::class, 'toggle'])->name('trunks.toggle');

        // Contextes d'appel
        Route::resource('contexts', CallContextController::class)->except(['show']);
        Route::post('contexts/{context}/toggle', [CallContextController::class, 'toggle'])->name('contexts.toggle');
        Route::get('contexts-dialplan', [CallContextController::class, 'dialplan'])->name('contexts.dialplan');

        // Scenarios d'appels (Call Flows)
        Route::resource('callflows', CallFlowController::class)->except(['show']);
        Route::post('callflows/{callflow}/toggle', [CallFlowController::class, 'toggle'])->name('callflows.toggle');
        Route::get('callflows/{callflow}/dialplan', [CallFlowController::class, 'dialplan'])->name('callflows.dialplan');
        Route::post('callflows/preview', [CallFlowController::class, 'preview'])->name('callflows.preview');
        Route::post('callflows/save-template', [CallFlowController::class, 'saveTemplate'])->name('callflows.save-template');
        Route::delete('callflows/templates/{template}', [CallFlowController::class, 'deleteTemplate'])->name('callflows.delete-template');

        // Routes sortantes
        Route::get('outbound', [OutboundRouteController::class, 'index'])->name('outbound.index');
        Route::get('outbound/create', [OutboundRouteController::class, 'create'])->name('outbound.create');
        Route::post('outbound', [OutboundRouteController::class, 'store'])->name('outbound.store');
        Route::get('outbound/{outbound_route}/edit', [OutboundRouteController::class, 'edit'])->name('outbound.edit');
        Route::put('outbound/{outbound_route}', [OutboundRouteController::class, 'update'])->name('outbound.update');
        Route::delete('outbound/{outbound_route}', [OutboundRouteController::class, 'destroy'])->name('outbound.destroy');
        Route::post('outbound/{outbound_route}/toggle', [OutboundRouteController::class, 'toggle'])->name('outbound.toggle');
        Route::post('outbound/reorder', [OutboundRouteController::class, 'reorder'])->name('outbound.reorder');

        // Files d'attente
        Route::resource('queues', CallQueueController::class)->except(['show']);

        // Salles de conference
        Route::resource('conferences', ConferenceRoomController::class)->except(['show']);
        Route::post('conferences/{conference}/toggle', [ConferenceRoomController::class, 'toggle'])->name('conferences.toggle');

        // Messagerie vocale (admin)
        Route::get('voicemail', [VoicemailController::class, 'index'])->name('voicemail.index');
        Route::get('voicemail/{extension}/{folder}/{file}/play', [VoicemailController::class, 'play'])->name('voicemail.play');
        Route::delete('voicemail/{extension}/{folder}/{file}', [VoicemailController::class, 'destroy'])->name('voicemail.destroy');

        // Journal d'appels
        Route::get('logs', [CallLogController::class, 'index'])->name('logs.index');

        // Supervision en direct
        Route::get('live', [LiveController::class, 'index'])->name('live.index');
        Route::get('live/poll', [LiveController::class, 'poll'])->name('live.poll');

        // Asterisk Console
        Route::get('asterisk/logs', [AsteriskLogController::class, 'index'])->name('asterisk.logs');
        Route::get('asterisk/logs/tail', [AsteriskLogController::class, 'tail'])->name('asterisk.logs.tail');
        Route::get('asterisk/command', [AsteriskLogController::class, 'command'])->name('asterisk.command');

        // Parametres
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::put('settings/smtp', [SettingController::class, 'updateSmtp'])->name('settings.smtp.update');
        Route::post('settings/smtp/test', [SettingController::class, 'testSmtp'])->name('settings.smtp.test');

        // Fichiers audio
        Route::get('audio', [AudioController::class, 'index'])->name('audio.index');
        Route::post('audio/upload', [AudioController::class, 'upload'])->name('audio.upload');
        Route::delete('audio/{audio}', [AudioController::class, 'destroy'])->name('audio.destroy');
        Route::get('audio/{audio}/play', [AudioController::class, 'play'])->name('audio.play');
        Route::get('api/audio', [AudioController::class, 'api'])->name('audio.api');

        // Musiques d'attente (MOH)
        Route::get('moh', [MohController::class, 'index'])->name('moh.index');
        Route::post('moh/set-default', [MohController::class, 'setDefault'])->name('moh.set-default');
        Route::post('moh/reset', [MohController::class, 'resetDefault'])->name('moh.reset');
        Route::get('moh/{class}/{file}/play', [MohController::class, 'play'])->name('moh.play');
        Route::post('moh/streams', [MohController::class, 'storeStream'])->name('moh.streams.store');
        Route::post('moh/streams/{stream}/toggle', [MohController::class, 'toggleStream'])->name('moh.streams.toggle');
        Route::delete('moh/streams/{stream}', [MohController::class, 'destroyStream'])->name('moh.streams.destroy');
        Route::post('moh/playlists', [MohController::class, 'storePlaylist'])->name('moh.playlists.store');
        Route::put('moh/playlists/{playlist}', [MohController::class, 'updatePlaylist'])->name('moh.playlists.update');
        Route::post('moh/playlists/{playlist}/toggle', [MohController::class, 'togglePlaylist'])->name('moh.playlists.toggle');
        Route::delete('moh/playlists/{playlist}', [MohController::class, 'destroyPlaylist'])->name('moh.playlists.destroy');
        Route::get('api/moh', [MohController::class, 'api'])->name('moh.api');

        // Logs systeme (activite)
        Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');

        // Firewall SIP
        Route::get('firewall', [FirewallController::class, 'index'])->name('firewall.index');
        Route::post('firewall', [FirewallController::class, 'store'])->name('firewall.store');
        Route::post('firewall/mode', [FirewallController::class, 'setMode'])->name('firewall.mode');
        Route::post('firewall/{rule}/toggle', [FirewallController::class, 'toggle'])->name('firewall.toggle');
        Route::delete('firewall/{rule}', [FirewallController::class, 'destroy'])->name('firewall.destroy');
        Route::post('firewall/unban', [FirewallController::class, 'unban'])->name('firewall.unban');

        // Codecs (page statique depuis config)
        Route::view('codecs', 'codecs.index')->name('codecs.index');
    });
});

require __DIR__ . '/auth.php';
