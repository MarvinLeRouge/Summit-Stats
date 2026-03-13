<?php

use App\Services\Gpx\SegmentationService;

beforeEach(function () {
    $this->service = new SegmentationService();
});

/**
 * Génère une liste de points simulant une pente constante.
 * $slope en %, $distanceM distance totale en mètres, $count nombre de points
 */
function generatePointsWithSlope(float $slope, float $distanceM, int $count = 2, string $startTime = '2024-06-15T08:00:00Z', float $startEle = 1000.0): array
{
    $points = [];
    $stepM  = $distanceM / ($count - 1);
    $dEle   = $stepM * ($slope / 100);
    $dLat   = $stepM / 111000;

    for ($i = 0; $i < $count; $i++) {
        $points[] = [
            'lat'  => 45.0 + ($i * $dLat),
            'lon'  => 6.0,
            'ele'  => $startEle + ($i * $dEle),
            'time' => \Carbon\Carbon::parse($startTime)->addSeconds($i * 60),
        ];
    }

    return $points;
}

it('classifies a flat track as slope class lt5', function () {
    $points   = generatePointsWithSlope(slope: 2, distanceM: 200, count: 3);
    $segments = $this->service->segment($points);

    expect($segments)->toHaveCount(1);
    expect($segments[0]['slope_class'])->toBe('lt5');
    expect($segments[0]['type'])->toBe('plat');
});

it('classifies a moderate climb as slope class 5_15', function () {
    $points   = generatePointsWithSlope(slope: 10, distanceM: 200, count: 3);
    $segments = $this->service->segment($points);

    expect($segments)->toHaveCount(1);
    expect($segments[0]['slope_class'])->toBe('5_15');
    expect($segments[0]['type'])->toBe('montee');
});

it('classifies a steep climb as slope class 25_35', function () {
    $points   = generatePointsWithSlope(slope: 30, distanceM: 100, count: 3);
    $segments = $this->service->segment($points);

    expect($segments)->toHaveCount(1);
    expect($segments[0]['slope_class'])->toBe('25_35');
    expect($segments[0]['type'])->toBe('montee');
});

it('classifies a descent correctly', function () {
    $points   = generatePointsWithSlope(slope: -15, distanceM: 200, count: 3);
    $segments = $this->service->segment($points);

    expect($segments)->toHaveCount(1);
    expect($segments[0]['type'])->toBe('descente');
    expect($segments[0]['slope_class'])->toBe('5_15');
});

it('merges consecutive points with same slope class into one segment', function () {
    $points   = generatePointsWithSlope(slope: 20, distanceM: 500, count: 5);
    $segments = $this->service->segment($points);

    expect($segments)->toHaveCount(1);
});

it('creates separate segments when slope class changes', function () {
    $first  = generatePointsWithSlope(slope: 8,  distanceM: 200, count: 2, startEle: 1000.0);
    
    // Partir de la position ET l'altitude du dernier point du premier groupe
    $lastPoint = end($first);
    $second = generatePointsWithSlope(slope: 28, distanceM: 100, count: 2, startEle: $lastPoint['ele']);
    
    // Corriger la latitude de départ du second groupe
    $latOffset = $lastPoint['lat'] - 45.0;
    foreach ($second as &$point) {
        $point['lat'] += $latOffset;
    }

    $points = array_merge($first, $second);
    $segments = $this->service->segment($points);

    expect($segments)->toHaveCount(3);
    expect($segments[0]['slope_class'])->toBe('5_15');
    expect($segments[2]['slope_class'])->toBe('25_35');
});

it('assigns correct order to segments', function () {
    $points = array_merge(
        generatePointsWithSlope(slope: 8,  distanceM: 200, count: 3),
        generatePointsWithSlope(slope: 28, distanceM: 100, count: 3),
    );

    $segments = $this->service->segment($points);

    expect($segments[0]['order'])->toBe(1);
    expect($segments[1]['order'])->toBe(2);
});

it('sets correct point indexes', function () {
    $points   = generatePointsWithSlope(slope: 10, distanceM: 200, count: 4);
    $segments = $this->service->segment($points);

    expect($segments[0]['point_index_start'])->toBe(0);
    expect($segments[0]['point_index_end'])->toBe(3);
});

it('classifies extreme slope correctly', function () {
    $points   = generatePointsWithSlope(slope: 48, distanceM: 100, count: 3);
    $segments = $this->service->segment($points);

    expect($segments[0]['slope_class'])->toBe('gt35');
});

it('returns empty array when trace has only one point', function () {
    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => 1000.0, 'time' => null],
    ];

    expect($this->service->segment($points))->toBeEmpty();
});

it('classifies slope above 35% as gt35', function () {
    $points = [
        ['lat' => 45.0,    'lon' => 6.0, 'ele' => 1000.0, 'time' => null],
        ['lat' => 45.0001, 'lon' => 6.0, 'ele' => 1050.0, 'time' => null],
    ];

    $segments = $this->service->segment($points);
    expect($segments[0]['slope_class'])->toBe('gt35');
});