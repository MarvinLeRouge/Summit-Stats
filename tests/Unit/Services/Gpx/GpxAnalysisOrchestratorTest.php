<?php

use App\Exceptions\GpxParseException;
use App\Services\Gpx\GpxAnalysisOrchestrator;

it('produces coherent stats from a simple GPX fixture', function () {
    $orchestrator = app(GpxAnalysisOrchestrator::class);
    $result       = $orchestrator->analyze(base_path('tests/Fixtures/gpx/simple_track.gpx'));

    expect($result)->toHaveKeys(['activity_stats', 'segments', 'points']);
    expect($result['activity_stats']['distance_km'])->toBeGreaterThan(0);
    expect($result['activity_stats']['elevation_gain'])->toBeGreaterThanOrEqual(0);
    expect($result['segments'])->not->toBeEmpty();
    expect($result['points'])->not->toBeEmpty();
    expect($result['points'][0])->toHaveKey('distance_from_start_km');
    expect($result['points'][0]['distance_from_start_km'])->toBe(0.0);
});

it('throws an exception for an invalid GPX file', function () {
    $orchestrator = app(GpxAnalysisOrchestrator::class);
    $orchestrator->analyze(base_path('tests/Fixtures/gpx/invalid.gpx'));
})->throws(GpxParseException::class);

it('analyse correctement un GPX sans timing', function () {
    $orchestrator = app(GpxAnalysisOrchestrator::class);
    $result       = $orchestrator->analyze(base_path('tests/Fixtures/gpx/no_time.gpx'));

    expect($result)->toHaveKeys(['activity_stats', 'segments', 'points']);
    expect($result['activity_stats']['duration_seconds'])->toBe(0);
    expect($result['activity_stats']['moving_duration_seconds'])->toBe(0);
    expect($result['activity_stats']['avg_speed_kmh'])->toBeNull();
    expect($result['activity_stats']['avg_speed_moving_kmh'])->toBeNull();
    expect($result['points'])->not->toBeEmpty();
    expect($result['points'][0]['distance_from_start_km'])->toBe(0.0);

    // Les points sans timing ont time null
    foreach ($result['points'] as $point) {
        expect($point['time'])->toBeNull();
    }
});