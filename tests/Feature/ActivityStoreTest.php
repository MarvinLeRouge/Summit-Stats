<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->user  = User::factory()->create();
    $this->token = $this->user->createToken('test')->plainTextToken;
});

it('creates an activity from a GPX file', function () {
    $gpxFile = UploadedFile::fake()->createWithContent(
        'trace.gpx',
        file_get_contents(base_path('tests/Fixtures/gpx/simple_track.gpx'))
    );

    $response = $this->withToken($this->token)
        ->postJson('/api/activities', [
            'title'       => 'Sortie test',
            'type'        => 'randonnee',
            'environment' => 'montagne',
            'date'        => '2024-06-15',
            'comment'     => 'Premier test',
            'gpx_file'    => $gpxFile,
        ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'success',
            'data' => [
                'id', 'title', 'type', 'environment', 'date',
                'distance_km', 'elevation_gain', 'duration_seconds',
                'segments',
            ],
        ]);

    $this->assertDatabaseHas('activities', ['title' => 'Sortie test']);
});

it('returns 422 if GPX file is missing', function () {
    $this->withToken($this->token)
        ->postJson('/api/activities', [
            'title'       => 'Sans fichier',
            'type'        => 'randonnee',
            'environment' => 'montagne',
            'date'        => '2024-06-15',
        ])
        ->assertUnprocessable();
});

it('returns 422 if type is invalid', function () {
    $gpxFile = UploadedFile::fake()->createWithContent(
        'trace.gpx',
        file_get_contents(base_path('tests/Fixtures/gpx/simple_track.gpx'))
    );

    $this->withToken($this->token)
        ->postJson('/api/activities', [
            'title'       => 'Type invalide',
            'type'        => 'cyclisme',
            'environment' => 'montagne',
            'date'        => '2024-06-15',
            'gpx_file'    => $gpxFile,
        ])
        ->assertUnprocessable();
});

it('returns 401 without token', function () {
    $this->postJson('/api/activities', [])
        ->assertUnauthorized();
});