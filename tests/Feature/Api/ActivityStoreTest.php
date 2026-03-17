<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake('local');
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('creates an activity from a GPX file', function () {
    $data = postActivityWithGpx($this, base_path('tests/Fixtures/gpx/simple_track.gpx'), [
        'title'   => 'Sortie test',
        'comment' => 'Premier test',
    ]);

    expect($data)->not->toBeEmpty();
    expect($data['activity'])->toHaveKeys([
        'id', 'title', 'type', 'environment', 'date',
        'distance_km', 'elevation_gain', 'duration_seconds',
    ]);

    $this->assertDatabaseHas('activities', ['title' => 'Sortie test']);
});

it('returns 422 if GPX file is missing', function () {
    $this->postJson('/api/activities', [
        'title'       => 'Sans fichier',
        'type'        => 'randonnee',
        'environment' => 'montagne',
        'date'        => '2024-06-15',
    ])->assertUnprocessable();
});

it('returns 422 if type is invalid', function () {
    $gpxFile = UploadedFile::fake()->createWithContent(
        'trace.gpx',
        file_get_contents(base_path('tests/Fixtures/gpx/simple_track.gpx'))
    );

    $this->postJson('/api/activities', [
        'title'       => 'Type invalide',
        'type'        => 'cyclisme',
        'environment' => 'montagne',
        'date'        => '2024-06-15',
        'gpx_file'    => $gpxFile,
    ])->assertUnprocessable();
});

