<?php

use App\Models\Activity;
use App\Models\TrackPoint;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('retourne les points de la trace pour une activité', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create();

    TrackPoint::factory()->count(5)->create([
        'activity_id' => $activity->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/activities/{$activity->id}/track");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['order', 'lat', 'lon', 'ele', 'distance_from_start_km'],
            ],
        ])
        ->assertJsonCount(5, 'data');
});

it('retourne les points dans le bon ordre', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create();

    foreach ([2, 0, 1, 4, 3] as $order) {
        TrackPoint::factory()->create([
            'activity_id' => $activity->id,
            'order' => $order,
        ]);
    }

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/activities/{$activity->id}/track");

    $orders = collect($response->json('data'))->pluck('order')->toArray();
    expect($orders)->toBe([0, 1, 2, 3, 4]);
});

it('retourne une liste vide si aucun point', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/activities/{$activity->id}/track");

    $response->assertStatus(200)
        ->assertJsonCount(0, 'data');
});

it('retourne 404 si l\'activité n\'existe pas', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/activities/99999/track')
        ->assertStatus(404);
});

it('retourne les points avec ele null pour un GPX sans altitude', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create();

    TrackPoint::factory()->count(3)->withoutElevation()->create([
        'activity_id' => $activity->id,
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/activities/{$activity->id}/track");

    $response->assertStatus(200);
    $points = $response->json('data');
    expect(array_column($points, 'ele'))->each->toBeNull();
});

it('retourne 401 sans authentification', function () {
    $activity = Activity::factory()->create();

    $this->getJson("/api/activities/{$activity->id}/track")
        ->assertStatus(401);
});
