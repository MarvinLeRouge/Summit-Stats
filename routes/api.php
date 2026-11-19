<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\SentryTunnelController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

Route::post('login', [LoginController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [LoginController::class, 'logout']);
    // Activities
    Route::apiResource('activities', ActivityController::class);
    Route::post('activities/{activity}/recalculate', [ActivityController::class, 'recalculate']);
    Route::get('/activities/{activity}/track', [ActivityController::class, 'track']);
    // Stats
    Route::get('stats', [StatsController::class, 'index']);
});

// Browser Sentry events relayed past ad-blockers (path = hash of the DSN, see SentryTunnelService).
// Outside Sanctum so errors on the login page are reported too; declared last so it shadows no route.
Route::post('{tunnelPath}', SentryTunnelController::class)
    ->where('tunnelPath', '[0-9a-z]{1,7}')
    ->middleware('throttle:sentry-tunnel');
