<?php

use App\Models\Activity;
use App\Models\TrackPoint;

it('appartient à une activité', function () {
    $activity = Activity::factory()->create();
    $trackPoint = TrackPoint::factory()->create(['activity_id' => $activity->id]);

    expect($trackPoint->activity)->toBeInstanceOf(Activity::class);
    expect($trackPoint->activity->id)->toBe($activity->id);
});

it('a les bons casts', function () {
    $trackPoint = TrackPoint::factory()->create([
        'lat' => '45.8326',
        'lon' => '6.8652',
        'ele' => '1034.5',
        'distance_from_start_km' => '1.234',
        'order' => '5',
    ]);

    expect($trackPoint->lat)->toBeFloat();
    expect($trackPoint->lon)->toBeFloat();
    expect($trackPoint->ele)->toBeFloat();
    expect($trackPoint->distance_from_start_km)->toBeFloat();
    expect($trackPoint->order)->toBeInt();
});

it('accepte une élévation nulle', function () {
    $trackPoint = TrackPoint::factory()->withoutElevation()->create();

    expect($trackPoint->ele)->toBeNull();
});

it('accepte un timestamp nul', function () {
    $trackPoint = TrackPoint::factory()->withoutTiming()->create();

    expect($trackPoint->time)->toBeNull();
});
