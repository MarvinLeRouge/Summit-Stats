<?php

use App\Services\Gpx\ElevationEnrichmentService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new ElevationEnrichmentService;
});

it('retourne les points inchangés si le service est désactivé', function () {
    config(['geo.elevation_enabled' => false]);

    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => null, 'time' => null],
        ['lat' => 45.1, 'lon' => 6.1, 'ele' => null, 'time' => null],
    ];

    $result = $this->service->enrich($points);

    expect($result)->toBe($points);
});

it('retourne les points inchangés si tableau vide', function () {
    config(['geo.elevation_enabled' => true]);

    $result = $this->service->enrich([]);

    expect($result)->toBe([]);
});

it('retourne les points inchangés si élévation déjà présente', function () {
    config(['geo.elevation_enabled' => true]);

    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => 1000.0, 'time' => null],
        ['lat' => 45.1, 'lon' => 6.1, 'ele' => 1100.0, 'time' => null],
    ];

    $result = $this->service->enrich($points);

    expect($result)->toBe($points);
});

it('détecte correctement que des points ont besoin d\'enrichissement', function () {
    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => null, 'time' => null],
        ['lat' => 45.1, 'lon' => 6.1, 'ele' => null, 'time' => null],
    ];

    expect($this->service->needsEnrichment($points))->toBeTrue();
});

it('détecte correctement que des points n\'ont pas besoin d\'enrichissement', function () {
    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => 1000.0, 'time' => null],
        ['lat' => 45.1, 'lon' => 6.1, 'ele' => null,   'time' => null],
    ];

    expect($this->service->needsEnrichment($points))->toBeFalse();
});

it('enrichit les points avec les altitudes de l\'API', function () {
    config([
        'geo.elevation_enabled' => true,
        'geo.elevation_endpoint' => 'https://api.opentopodata.org/v1/mapzen',
        'geo.elevation_max_points_per_req' => 100,
        'geo.elevation_rate_delay_s' => 0,
    ]);

    Http::fake([
        '*' => Http::response([
            'results' => [
                ['elevation' => 1034],
                ['elevation' => 1045],
            ],
        ], 200),
    ]);

    $points = [
        ['lat' => 45.8326, 'lon' => 6.8652, 'ele' => null, 'time' => null],
        ['lat' => 45.8330, 'lon' => 6.8660, 'ele' => null, 'time' => null],
    ];

    $result = $this->service->enrich($points);

    expect($result[0]['ele'])->toBe(1034.0);
    expect($result[1]['ele'])->toBe(1045.0);
});

it('retourne ele null si l\'API échoue', function () {
    config([
        'geo.elevation_enabled' => true,
        'geo.elevation_endpoint' => 'https://api.opentopodata.org/v1/mapzen',
        'geo.elevation_max_points_per_req' => 100,
        'geo.elevation_rate_delay_s' => 0,
    ]);

    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $points = [
        ['lat' => 45.8326, 'lon' => 6.8652, 'ele' => null, 'time' => null],
        ['lat' => 45.8330, 'lon' => 6.8660, 'ele' => null, 'time' => null],
    ];

    $result = $this->service->enrich($points);

    expect($result[0]['ele'])->toBeNull();
    expect($result[1]['ele'])->toBeNull();
});

it('appelle le callback de progression après chaque chunk', function () {
    config([
        'geo.elevation_enabled' => true,
        'geo.elevation_endpoint' => 'https://api.opentopodata.org/v1/mapzen',
        'geo.elevation_max_points_per_req' => 1,
        'geo.elevation_rate_delay_s' => 0,
    ]);

    Http::fake([
        '*' => Http::response([
            'results' => [['elevation' => 1000]],
        ], 200),
    ]);

    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => null, 'time' => null],
        ['lat' => 45.1, 'lon' => 6.1, 'ele' => null, 'time' => null],
    ];

    $progressValues = [];
    $this->service->enrich($points, function (int $percent) use (&$progressValues) {
        $progressValues[] = $percent;
    });

    expect($progressValues)->toBe([50, 100]);
});

it('gère gracieusement une exception réseau', function () {
    config([
        'geo.elevation_enabled' => true,
        'geo.elevation_endpoint' => 'https://api.opentopodata.org/v1/mapzen',
        'geo.elevation_max_points_per_req' => 100,
        'geo.elevation_rate_delay_s' => 0,
    ]);

    Http::fake([
        '*' => fn () => throw new Exception('Connection refused'),
    ]);

    $points = [
        ['lat' => 45.8326, 'lon' => 6.8652, 'ele' => null, 'time' => null],
        ['lat' => 45.8330, 'lon' => 6.8660, 'ele' => null, 'time' => null],
    ];

    $result = $this->service->enrich($points);

    expect($result[0]['ele'])->toBeNull();
    expect($result[1]['ele'])->toBeNull();
});

it('ignore les résultats API en surplus par rapport aux points envoyés', function () {
    config([
        'geo.elevation_enabled' => true,
        'geo.elevation_endpoint' => 'https://api.opentopodata.org/v1/mapzen',
        'geo.elevation_max_points_per_req' => 100,
        'geo.elevation_rate_delay_s' => 0,
    ]);

    Http::fake([
        '*' => Http::response([
            'results' => [
                ['elevation' => 1034],
                ['elevation' => 1045],
                ['elevation' => 9999], // surplus — pas de point correspondant
            ],
        ], 200),
    ]);

    $points = [
        ['lat' => 45.8326, 'lon' => 6.8652, 'ele' => null, 'time' => null],
        ['lat' => 45.8330, 'lon' => 6.8660, 'ele' => null, 'time' => null],
    ];

    $result = $this->service->enrich($points);

    expect($result[0]['ele'])->toBe(1034.0);
    expect($result[1]['ele'])->toBe(1045.0);
    expect(count($result))->toBe(2);
});

it('stocke null si l\'élévation retournée par l\'API n\'est pas numérique', function () {
    config([
        'geo.elevation_enabled' => true,
        'geo.elevation_endpoint' => 'https://api.opentopodata.org/v1/mapzen',
        'geo.elevation_max_points_per_req' => 100,
        'geo.elevation_rate_delay_s' => 0,
    ]);

    Http::fake([
        '*' => Http::response([
            'results' => [
                ['elevation' => null],
                ['elevation' => 'invalid'],
            ],
        ], 200),
    ]);

    $points = [
        ['lat' => 45.8326, 'lon' => 6.8652, 'ele' => null, 'time' => null],
        ['lat' => 45.8330, 'lon' => 6.8660, 'ele' => null, 'time' => null],
    ];

    $result = $this->service->enrich($points);

    expect($result[0]['ele'])->toBeNull();
    expect($result[1]['ele'])->toBeNull();
});
