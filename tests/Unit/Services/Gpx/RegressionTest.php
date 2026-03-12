<?php

use App\Services\Gpx\GpxAnalysisOrchestrator;

it('produces coherent stats from a real GPX track', function () {
    $result = app(GpxAnalysisOrchestrator::class)
        ->analyze(base_path('tests/Fixtures/gpx/real_track.gpx'));

    $stats = $result['activity_stats'];

    // Distance : ~11.80 km ± 5%
    expect($stats['distance_km'])->toBeBetween(11.2, 12.4);

    // D+ : ~462m ± 10%
    expect($stats['elevation_gain'])->toBeBetween(415, 510);

    // D- : ~471m ± 10%
    expect($stats['elevation_loss'])->toBeBetween(424, 518);

    // Durée : 11831 secondes ± 1%
    expect($stats['duration_seconds'])->toBeBetween(11713, 11950);

    // Segments non vides
    expect($result['segments'])->not->toBeEmpty();
});