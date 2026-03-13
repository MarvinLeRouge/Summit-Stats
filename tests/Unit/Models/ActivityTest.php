<?php

use App\Models\Activity;
use App\Models\Segment;

it('has segments relation', function () {
    $activity = Activity::factory()->create();
    Segment::factory()->count(3)->create(['activity_id' => $activity->id]);

    expect($activity->segments)->toHaveCount(3);
    expect($activity->segments->first())->toBeInstanceOf(Segment::class);
});

it('formats duration correctly', function () {
    $activity = Activity::factory()->make(['duration_seconds' => 11831]);

    expect($activity->formatted_duration)->toBe('3h17');
});

it('formats duration with zero minutes', function () {
    $activity = Activity::factory()->make(['duration_seconds' => 3600]);

    expect($activity->formatted_duration)->toBe('1h00');
});

it('casts date as carbon instance', function () {
    $activity = Activity::factory()->make(['date' => '2024-06-15']);

    expect($activity->date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('casts numeric fields as correct types', function () {
    $activity = Activity::factory()->make([
        'distance_km'    => '11.5',
        'elevation_gain' => '470',
        'duration_seconds' => '11831',
    ]);

    expect($activity->distance_km)->toBeFloat();
    expect($activity->elevation_gain)->toBeInt();
    expect($activity->duration_seconds)->toBeInt();
});