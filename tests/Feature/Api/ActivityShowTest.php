<?php

use App\Models\Activity;
use App\Models\Segment;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test')->plainTextToken;
});

it('returns activity detail with segments', function () {
    $activity = Activity::factory()->create();
    Segment::factory()->count(3)->create(['activity_id' => $activity->id]);

    $response = $this->withToken($this->token)
        ->getJson("/api/activities/{$activity->id}")
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'id', 'title', 'type', 'environment', 'date',
                'distance_km', 'elevation_gain', 'duration_seconds',
                'segments' => [['id', 'type', 'slope_class', 'distance_km']],
            ],
        ]);

    expect($response->json('data.segments'))->toHaveCount(3);
});

it('returns 404 for unknown activity', function () {
    $this->withToken($this->token)
        ->getJson('/api/activities/999')
        ->assertNotFound();
});

it('returns 401 without token', function () {
    $activity = Activity::factory()->create();

    $this->getJson("/api/activities/{$activity->id}")
        ->assertUnauthorized();
});
