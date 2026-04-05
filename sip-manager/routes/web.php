<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SipLineController;
use App\Http\Controllers\TrunkController;
use App\Http\Controllers\CallContextController;
use App\Http\Controllers\CallLogController;
use App\Http\Controllers\AsteriskLogController;
use App\Http\Controllers\CallFlowController;
use App\Http\Controllers\CallQueueController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Lignes SIP
    Route::resource('lines', SipLineController::class)->except(['show']);
    Route::post('lines/{line}/toggle', [SipLineController::class, 'toggle'])
         ->name('lines.toggle');

    // Trunks
    Route::resource('trunks', TrunkController::class)->except(['show']);
    Route::post('trunks/{trunk}/toggle', [TrunkController::class, 'toggle'])
         ->name('trunks.toggle');

    // Contextes d'appel
    Route::resource('contexts', CallContextController::class)->except(['show']);
    Route::post('contexts/{context}/toggle', [CallContextController::class, 'toggle'])
         ->name('contexts.toggle');
    Route::get('contexts-dialplan', [CallContextController::class, 'dialplan'])
         ->name('contexts.dialplan');

    // Scenarios d'appels (Call Flows)
    Route::resource('callflows', CallFlowController::class)->except(['show']);
    Route::post('callflows/{callflow}/toggle', [CallFlowController::class, 'toggle'])
         ->name('callflows.toggle');
    Route::get('callflows/{callflow}/dialplan', [CallFlowController::class, 'dialplan'])
         ->name('callflows.dialplan');
    Route::post('callflows/preview', [CallFlowController::class, 'preview'])
         ->name('callflows.preview');
    Route::post('callflows/save-template', [CallFlowController::class, 'saveTemplate'])
         ->name('callflows.save-template');
    Route::delete('callflows/templates/{template}', [CallFlowController::class, 'deleteTemplate'])
         ->name('callflows.delete-template');

    // Files d'attente
    Route::resource('queues', CallQueueController::class)->except(['show']);

    // Journal d'appels
    Route::get('logs', [CallLogController::class, 'index'])->name('logs.index');

    // Asterisk Console
    Route::get('asterisk/logs', [AsteriskLogController::class, 'index'])->name('asterisk.logs');
    Route::get('asterisk/logs/tail', [AsteriskLogController::class, 'tail'])->name('asterisk.logs.tail');
    Route::get('asterisk/command', [AsteriskLogController::class, 'command'])->name('asterisk.command');

    // Parametres
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

    // Codecs (page statique depuis config)
    Route::view('codecs', 'codecs.index')->name('codecs.index');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
