<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

Route::post('login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [LoginController::class, 'logout']);
    // Activities
    Route::apiResource('activities', ActivityController::class);
    Route::post('activities/{activity}/recalculate', [ActivityController::class, 'recalculate']);
    Route::get('/activities/{activity}/track', [ActivityController::class, 'track']);
    // Stats
    Route::get('stats', [StatsController::class, 'index']);
});
