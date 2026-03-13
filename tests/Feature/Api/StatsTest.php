<?php

use App\Models\Activity;
use App\Models\Segment;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test')->plainTextToken;
});

it('returns progression data for a metric', function () {
    $activity = Activity::factory()->create(['date' => '2024-06-15', 'type' => 'trail']);
    Segment::factory()->create([
        'activity_id' => $activity->id,
        'type' => 'montee',
        'avg_ascent_speed_mh' => 450.0,
        'avg_slope_pct' => 20.0,
    ]);

    $response = $this->withToken($this->token)
        ->getJson('/api/stats?metric=avg_ascent_speed_mh')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [['date', 'value', 'activity_title']],
            'meta' => ['metric', 'unit', 'count'],
        ]);

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('meta.metric'))->toBe('avg_ascent_speed_mh');
});

it('filters by activity type', function () {
    $trail = Activity::factory()->create(['type' => 'trail', 'date' => '2024-06-15']);
    $randonnee = Activity::factory()->create(['type' => 'randonnee', 'date' => '2024-06-16']);

    Segment::factory()->create(['activity_id' => $trail->id,     'type' => 'montee', 'avg_ascent_speed_mh' => 400.0, 'avg_slope_pct' => 20.0]);
    Segment::factory()->create(['activity_id' => $randonnee->id, 'type' => 'montee', 'avg_ascent_speed_mh' => 350.0, 'avg_slope_pct' => 20.0]);

    $response = $this->withToken($this->token)
        ->getJson('/api/stats?metric=avg_ascent_speed_mh&type=trail')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('filters by slope interval', function () {
    $activity = Activity::factory()->create(['date' => '2024-06-15']);

    Segment::factory()->create(['activity_id' => $activity->id, 'type' => 'montee', 'avg_slope_pct' => 10.0, 'avg_ascent_speed_mh' => 300.0]);
    Segment::factory()->create(['activity_id' => $activity->id, 'type' => 'montee', 'avg_slope_pct' => 25.0, 'avg_ascent_speed_mh' => 400.0]);

    $response = $this->withToken($this->token)
        ->getJson('/api/stats?metric=avg_ascent_speed_mh&slope_min=15')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('returns 422 if metric is missing', function () {
    $this->withToken($this->token)
        ->getJson('/api/stats')
        ->assertUnprocessable();
});

it('returns 401 without token', function () {
    $this->getJson('/api/stats?metric=avg_ascent_speed_mh')
        ->assertUnauthorized();
});

it('returns empty data when no activities match filters', function () {
    $response = $this->withToken($this->token)
        ->getJson('/api/stats?metric=avg_ascent_speed_mh&type=trail')
        ->assertOk();

    expect($response->json('data'))->toBeEmpty();
});

it('returns elevation_gain metric for activities', function () {
    Activity::factory()->create(['date' => '2024-06-15', 'elevation_gain' => 500]);
    Activity::factory()->create(['date' => '2024-07-15', 'elevation_gain' => 700]);

    $response = $this->withToken($this->token)
        ->getJson('/api/stats?metric=elevation_gain')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('meta.unit'))->toBe('m');
});

it('returns distance_km metric for activities', function () {
    Activity::factory()->create(['date' => '2024-06-15', 'distance_km' => 10.5]);

    $response = $this->withToken($this->token)
        ->getJson('/api/stats?metric=distance_km')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('meta.unit'))->toBe('km');
});

it('returns elevation_gain filtered by slope', function () {
    $activity = Activity::factory()->create(['date' => '2024-06-15']);
    Segment::factory()->create(['activity_id' => $activity->id, 'avg_slope_pct' => 20.0]);

    $response = $this->withToken($this->token)
        ->getJson('/api/stats?metric=elevation_gain&slope_min=15')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('returns avg_speed_moving_kmh metric', function () {
    $activity = Activity::factory()->create(['date' => '2024-06-15', 'type' => 'trail']);
    Segment::factory()->create([
        'activity_id' => $activity->id,
        'type' => 'montee',
        'avg_speed_kmh' => 4.5,
        'avg_slope_pct' => 10.0,
    ]);

    $response = $this->withToken($this->token)
        ->getJson('/api/stats?metric=avg_speed_kmh')
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});
