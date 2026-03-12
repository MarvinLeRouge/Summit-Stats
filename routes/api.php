<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    // Activities
    Route::apiResource('activities', \App\Http\Controllers\Api\ActivityController::class);

    // Stats
    // Route::get('stats', [\App\Http\Controllers\Api\StatsController::class, 'index']);
});