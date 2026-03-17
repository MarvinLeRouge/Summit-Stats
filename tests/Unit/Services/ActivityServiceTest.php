<?php

use App\Services\ActivityService;
use Illuminate\Http\UploadedFile;

it('détecte qu\'un GPX nécessite un enrichissement altimétrique', function () {
    $service = app(ActivityService::class);
    $file = new UploadedFile(
        base_path('tests/Fixtures/gpx/no_elevation.gpx'),
        'no_elevation.gpx', 'application/gpx+xml', null, true
    );

    expect($service->needsElevationEnrichment($file))->toBeTrue();
});

it('détecte qu\'un GPX ne nécessite pas d\'enrichissement altimétrique', function () {
    $service = app(ActivityService::class);
    $file = new UploadedFile(
        base_path('tests/Fixtures/gpx/simple_track.gpx'),
        'simple_track.gpx', 'application/gpx+xml', null, true
    );

    expect($service->needsElevationEnrichment($file))->toBeFalse();
});
