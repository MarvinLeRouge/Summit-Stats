<?php

use App\Models\Activity;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test')->plainTextToken;
});

it('returns paginated list of activities', function () {
    Activity::factory()->count(5)->create();

    $this->withToken($this->token)
        ->getJson('/api/activities')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                'data' => [['id', 'title', 'type', 'environment', 'date', 'distance_km', 'elevation_gain']],
                'current_page',
                'total',
            ],
        ]);
});

it('filters activities by type', function () {
    Activity::factory()->create(['type' => 'trail']);
    Activity::factory()->create(['type' => 'randonnee']);

    $response = $this->withToken($this->token)
        ->getJson('/api/activities?type=trail')
        ->assertOk();

    expect($response->json('data.data'))->toHaveCount(1);
    expect($response->json('data.data.0.type'))->toBe('trail');
});

it('filters activities by environment', function () {
    Activity::factory()->create(['environment' => 'montagne']);
    Activity::factory()->create(['environment' => 'urbain']);

    $response = $this->withToken($this->token)
        ->getJson('/api/activities?environment=montagne')
        ->assertOk();

    expect($response->json('data.data'))->toHaveCount(1);
});

it('filters activities by date range', function () {
    Activity::factory()->create(['date' => '2024-01-15']);
    Activity::factory()->create(['date' => '2024-06-15']);
    Activity::factory()->create(['date' => '2024-12-15']);

    $response = $this->withToken($this->token)
        ->getJson('/api/activities?date_from=2024-03-01&date_to=2024-09-01')
        ->assertOk();

    expect($response->json('data.data'))->toHaveCount(1);
});

it('returns 401 without token', function () {
    $this->getJson('/api/activities')
        ->assertUnauthorized();
});
