<?php

use App\Services\Gpx\ElevationCalculatorService;
use Carbon\Carbon;

beforeEach(function () {
    $this->calculator = new ElevationCalculatorService;
});

it('calculates distance between two points using Haversine formula', function () {
    $points = [
        ['lat' => 45.9237, 'lon' => 6.8694, 'ele' => 1035.0, 'time' => null],
        ['lat' => 45.9772, 'lon' => 6.9217, 'ele' => 1253.0, 'time' => null],
    ];

    $distance = $this->calculator->totalDistance($points);

    expect($distance)->toBeBetween(7.0, 8.0);
});

it('calculates positive elevation gain', function () {
    $points = [
        ['lat' => 0, 'lon' => 0, 'ele' => 1000.0, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1050.0, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1020.0, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1100.0, 'time' => null],
    ];

    expect($this->calculator->elevationGain($points))->toBe(130);
});

it('calculates elevation loss', function () {
    $points = [
        ['lat' => 0, 'lon' => 0, 'ele' => 1000.0, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1050.0, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1020.0, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1100.0, 'time' => null],
    ];

    expect($this->calculator->elevationLoss($points))->toBe(30);
});

it('calculates total duration from timestamps', function () {
    $points = [
        ['lat' => 0, 'lon' => 0, 'ele' => 0, 'time' => Carbon::parse('2024-06-15T08:00:00Z')],
        ['lat' => 0, 'lon' => 0, 'ele' => 0, 'time' => Carbon::parse('2024-06-15T10:30:00Z')],
    ];

    expect($this->calculator->totalDuration($points))->toBe(9000);
});

it('returns zero duration if no timestamps', function () {
    $points = [
        ['lat' => 0, 'lon' => 0, 'ele' => 0, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 0, 'time' => null],
    ];

    expect($this->calculator->totalDuration($points))->toBe(0);
});

it('returns zero elevation gain on flat track', function () {
    $points = [
        ['lat' => 0, 'lon' => 0, 'ele' => 1000.0, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1000.0, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1000.0, 'time' => null],
    ];

    expect($this->calculator->elevationGain($points))->toBe(0);
    expect($this->calculator->elevationLoss($points))->toBe(0);
});

it('ignores small elevation variations below noise threshold', function () {
    $points = [
        ['lat' => 0, 'lon' => 0, 'ele' => 1000.0, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1000.3, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1000.1, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1000.2, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 1000.0, 'time' => null],
    ];

    expect($this->calculator->elevationGain($points))->toBe(0);
});

it('calculates moving duration excluding pauses', function () {
    $points = [
        ['lat' => 0, 'lon' => 0, 'ele' => 0, 'time' => Carbon::parse('2024-06-15T08:00:00Z')],
        ['lat' => 0, 'lon' => 0, 'ele' => 0, 'time' => Carbon::parse('2024-06-15T08:00:20Z')], // 20s — mouvement
        ['lat' => 0, 'lon' => 0, 'ele' => 0, 'time' => Carbon::parse('2024-06-15T08:05:20Z')], // 300s — pause
        ['lat' => 0, 'lon' => 0, 'ele' => 0, 'time' => Carbon::parse('2024-06-15T08:05:40Z')], // 20s — mouvement
    ];

    expect($this->calculator->movingDuration($points))->toBe(40);
});

it('handles null elevation in smoothing window', function () {
    $points = [
        ['lat' => 0, 'lon' => 0, 'ele' => null, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => null, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => null, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => null, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => null, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => null, 'time' => null],
    ];

    expect($this->calculator->elevationGain($points))->toBe(0);
    expect($this->calculator->elevationLoss($points))->toBe(0);
});

it('skips points without timestamp in movingDuration', function () {
    $points = [
        ['lat' => 0, 'lon' => 0, 'ele' => 0, 'time' => Carbon::parse('2024-06-15T08:00:00Z')],
        ['lat' => 0, 'lon' => 0, 'ele' => 0, 'time' => null],
        ['lat' => 0, 'lon' => 0, 'ele' => 0, 'time' => Carbon::parse('2024-06-15T08:00:20Z')],
    ];

    expect($this->calculator->movingDuration($points))->toBe(0);
});
