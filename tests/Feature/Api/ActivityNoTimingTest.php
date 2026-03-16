<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;

it('importe correctement un GPX sans timing', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $gpxPath = base_path('tests/Fixtures/gpx/no_time.gpx');
    $file    = new UploadedFile($gpxPath, 'no_time.gpx', 'application/gpx+xml', null, true);

    $response = $this->postJson('/api/activities', [
        'title'       => 'Trace sans timing',
        'type'        => 'randonnee',
        'environment' => 'montagne',
        'date'        => '2024-03-15',
        'gpx_file'    => $file,
    ]);

    $response->assertStatus(201);

    $activity = $response->json('data');
    expect($activity['duration_seconds'])->toBe(0);
    expect($activity['moving_duration_seconds'])->toBe(0);
    expect($activity['avg_speed_kmh'])->toBeNull();
    expect($activity['avg_speed_moving_kmh'])->toBeNull();
});

it('retourne les segments avec avg_speed_kmh null pour un GPX sans timing', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $gpxPath = base_path('tests/Fixtures/gpx/no_time.gpx');
    $file    = new UploadedFile($gpxPath, 'no_time.gpx', 'application/gpx+xml', null, true);

    $response = $this->postJson('/api/activities', [
        'title'       => 'Trace sans timing',
        'type'        => 'randonnee',
        'environment' => 'montagne',
        'date'        => '2024-03-15',
        'gpx_file'    => $file,
    ]);

    $activityId = $response->json('data.id');

    $detail = $this->getJson("/api/activities/{$activityId}");
    $detail->assertStatus(200);

    $segments = $detail->json('data.segments');
    foreach ($segments as $segment) {
        expect($segment['avg_speed_kmh'])->toBeNull();
    }
});

it('retourne les track points pour un GPX sans timing', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $gpxPath = base_path('tests/Fixtures/gpx/no_time.gpx');
    $file    = new UploadedFile($gpxPath, 'no_time.gpx', 'application/gpx+xml', null, true);

    $response = $this->postJson('/api/activities', [
        'title'       => 'Trace sans timing',
        'type'        => 'randonnee',
        'environment' => 'montagne',
        'date'        => '2024-03-15',
        'gpx_file'    => $file,
    ]);

    $activityId = $response->json('data.id');

    $track = $this->getJson("/api/activities/{$activityId}/track");
    $track->assertStatus(200);

    $points = $track->json('data');
    expect($points)->not->toBeEmpty();

    foreach ($points as $point) {
        expect($point)->toHaveKeys(['order', 'lat', 'lon', 'ele', 'distance_from_start_km']);
    }
});