<?php

use App\Services\Gpx\GpxAnalysisOrchestrator;

it('produces coherent stats from a real GPX track', function () {
    $result = app(GpxAnalysisOrchestrator::class)
        ->analyze(base_path('tests/Fixtures/gpx/real_track.gpx'));

    $stats = $result['activity_stats'];

    // Distance
    expect($stats['distance_km'])->toBeBetween(11.2, 12.4);

    // Dénivelés
    expect($stats['elevation_gain'])->toBeBetween(415, 510);
    expect($stats['elevation_loss'])->toBeBetween(424, 518);

    // Durées
    expect($stats['duration_seconds'])->toBeBetween(11713, 11950);
    expect($stats['moving_duration_seconds'])->toBeBetween(9000, 11950);

    // Vitesses
    expect($stats['avg_speed_kmh'])->toBeBetween(3.0, 4.5);
    expect($stats['avg_speed_moving_kmh'])->toBeBetween(4.0, 5.5);
    expect($stats['avg_ascent_speed_mh'])->toBeBetween(200, 700);

    // Vitesses ascensionnelles alternatives
    expect($stats['summit_ascent_speed_mh'])->toBeBetween(100, 700);
    expect($stats['longest_ascent_speed_mh'])->toBeBetween(100, 700);

    // Vitesses à plat et en descente
    expect($stats['avg_flat_speed_kmh'])->toBeBetween(2.0, 8.0);
    expect($stats['avg_descent_speed_kmh'])->toBeBetween(2.0, 10.0);
    expect($stats['avg_descent_rate_mh'])->toBeBetween(100, 800);

    // Répartition globale — somme ≈ 100%
    $pctTotal = $stats['pct_ascent'] + $stats['pct_flat'] + $stats['pct_descent'];
    expect($pctTotal)->toBeBetween(98.0, 102.0);

    // Segments non vides
    expect($result['segments'])->not->toBeEmpty();
});