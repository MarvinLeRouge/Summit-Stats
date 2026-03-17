<?php

use App\Services\Gpx\StatsAggregatorService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new StatsAggregatorService;
});

function makeSegment(array $points, string $type = 'montee', string $slopeClass = '5_15'): array
{
    return [
        'type' => $type,
        'slope_class' => $slopeClass,
        'order' => 1,
        'point_index_start' => 0,
        'point_index_end' => count($points) - 1,
        'points' => $points,
    ];
}

it('calculates distance for a segment', function () {
    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => 1000.0, 'time' => Carbon::parse('2024-06-15T08:00:00Z')],
        ['lat' => 45.01, 'lon' => 6.0, 'ele' => 1100.0, 'time' => Carbon::parse('2024-06-15T08:10:00Z')],
    ];

    $segment = makeSegment($points, 'montee', '5_15');
    $result = $this->service->aggregate([$segment]);

    expect($result['segments'][0]['distance_km'])->toBeGreaterThan(0);
});

it('calculates elevation delta for ascent segment', function () {
    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => 1000.0, 'time' => Carbon::parse('2024-06-15T08:00:00Z')],
        ['lat' => 45.01, 'lon' => 6.0, 'ele' => 1200.0, 'time' => Carbon::parse('2024-06-15T08:10:00Z')],
    ];

    $segment = makeSegment($points, 'montee', '15_25');
    $result = $this->service->aggregate([$segment]);

    expect($result['segments'][0]['elevation_delta'])->toBe(200);
});

it('calculates elevation delta for descent segment', function () {
    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => 1200.0, 'time' => Carbon::parse('2024-06-15T08:00:00Z')],
        ['lat' => 45.01, 'lon' => 6.0, 'ele' => 1000.0, 'time' => Carbon::parse('2024-06-15T08:10:00Z')],
    ];

    $segment = makeSegment($points, 'descente', '15_25');
    $result = $this->service->aggregate([$segment]);

    expect($result['segments'][0]['elevation_delta'])->toBe(-200);
});

it('calculates avg_ascent_speed for ascent segment', function () {
    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => 1000.0, 'time' => Carbon::parse('2024-06-15T08:00:00Z')],
        ['lat' => 45.01, 'lon' => 6.0, 'ele' => 1500.0, 'time' => Carbon::parse('2024-06-15T09:00:00Z')],
    ];

    $segment = makeSegment($points, 'montee', '25_35');
    $result = $this->service->aggregate([$segment]);

    // 500m D+ en 1h = 500 m/h
    expect($result['segments'][0]['avg_ascent_speed_mh'])->toBe(500.0);
});

it('sets avg_ascent_speed to null for descent segment', function () {
    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => 1500.0, 'time' => Carbon::parse('2024-06-15T08:00:00Z')],
        ['lat' => 45.01, 'lon' => 6.0, 'ele' => 1000.0, 'time' => Carbon::parse('2024-06-15T09:00:00Z')],
    ];

    $segment = makeSegment($points, 'descente', '25_35');
    $result = $this->service->aggregate([$segment]);

    expect($result['segments'][0]['avg_ascent_speed_mh'])->toBeNull();
});

it('calculates global elevation gain from positive segments only', function () {
    $ascent = makeSegment([
        ['lat' => 45.0,  'lon' => 6.0, 'ele' => 1000.0, 'time' => Carbon::parse('2024-06-15T08:00:00Z')],
        ['lat' => 45.01, 'lon' => 6.0, 'ele' => 1300.0, 'time' => Carbon::parse('2024-06-15T08:30:00Z')],
    ], 'montee', '25_35');

    $descent = makeSegment([
        ['lat' => 45.01, 'lon' => 6.0, 'ele' => 1300.0, 'time' => Carbon::parse('2024-06-15T08:30:00Z')],
        ['lat' => 45.02, 'lon' => 6.0, 'ele' => 1100.0, 'time' => Carbon::parse('2024-06-15T09:00:00Z')],
    ], 'descente', '15_25');

    $result = $this->service->aggregate([$ascent, $descent]);

    expect($result['activity_stats']['elevation_gain'])->toBe(300);
    expect($result['activity_stats']['elevation_loss'])->toBe(200);
});

it('calculates global avg_speed correctly', function () {
    $segment = makeSegment([
        ['lat' => 45.0,  'lon' => 6.0, 'ele' => 1000.0, 'time' => Carbon::parse('2024-06-15T08:00:00Z')],
        ['lat' => 45.09, 'lon' => 6.0, 'ele' => 1100.0, 'time' => Carbon::parse('2024-06-15T09:00:00Z')],
    ], 'montee', '5_15');

    $result = $this->service->aggregate([$segment]);

    expect($result['activity_stats']['avg_speed_kmh'])->toBeGreaterThan(0);
});

it('retourne une pente nulle si la distance du segment est zéro', function () {
    $points = [
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => 1000.0, 'time' => null, 'distance_from_start_km' => 0.0],
        ['lat' => 45.0, 'lon' => 6.0, 'ele' => 1000.0, 'time' => null, 'distance_from_start_km' => 0.0],
    ];

    $segments = [
        [
            'type' => 'plat',
            'slope_class' => 'lt5',
            'points' => $points,
            'point_index_start' => 0,
            'point_index_end' => 1,
        ],
    ];

    $result = $this->service->aggregate($segments, $points);

    expect($result['segments'][0]['avg_slope_pct'])->toBe(0.0);
});
