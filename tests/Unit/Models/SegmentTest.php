<?php

use App\Models\Activity;
use App\Models\Segment;

it('belongs to an activity', function () {
    $activity = Activity::factory()->create();
    $segment = Segment::factory()->create(['activity_id' => $activity->id]);

    expect($segment->activity)->toBeInstanceOf(Activity::class);
    expect($segment->activity->id)->toBe($activity->id);
});

it('isAscent() returns true for montee type', function () {
    $segment = Segment::factory()->make(['type' => 'montee']);
    expect($segment->isAscent())->toBeTrue();
});

it('isAscent() returns false for descente type', function () {
    $segment = Segment::factory()->make(['type' => 'descente']);
    expect($segment->isAscent())->toBeFalse();
});

it('isAscent() returns false for plat type', function () {
    $segment = Segment::factory()->make(['type' => 'plat']);
    expect($segment->isAscent())->toBeFalse();
});

it('casts numeric fields as correct types', function () {
    $segment = Segment::factory()->make([
        'distance_km' => '1.5',
        'elevation_delta' => '150',
        'duration_seconds' => '600',
        'avg_speed_kmh' => '4.5',
        'avg_slope_pct' => '20.0',
    ]);

    expect($segment->distance_km)->toBeFloat();
    expect($segment->elevation_delta)->toBeInt();
    expect($segment->duration_seconds)->toBeInt();
});
