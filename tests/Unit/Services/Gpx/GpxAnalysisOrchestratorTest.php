<?php

use App\Exceptions\GpxParseException;
use App\Services\Gpx\GpxAnalysisOrchestrator;

it('produces coherent stats from a simple GPX fixture', function () {
    $orchestrator = app(GpxAnalysisOrchestrator::class);
    $result = $orchestrator->analyze(base_path('tests/Fixtures/gpx/simple_track.gpx'));

    expect($result)->toHaveKeys(['activity_stats', 'segments']);
    expect($result['activity_stats']['distance_km'])->toBeGreaterThan(0);
    expect($result['activity_stats']['elevation_gain'])->toBeGreaterThanOrEqual(0);
    expect($result['segments'])->not->toBeEmpty();
});

it('throws an exception for an invalid GPX file', function () {
    $orchestrator = app(GpxAnalysisOrchestrator::class);
    $orchestrator->analyze(base_path('tests/Fixtures/gpx/invalid.gpx'));
})->throws(GpxParseException::class);
