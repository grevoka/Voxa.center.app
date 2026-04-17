<?php

use App\Http\Controllers\WidgetCallController;
use App\Http\Middleware\WidgetCors;
use Illuminate\Support\Facades\Route;

Route::middleware(WidgetCors::class)->group(function () {
    Route::get('widget/{token}/config', [WidgetCallController::class, 'config'])
        ->middleware('throttle:30,1')
        ->name('api.widget.config');
});
