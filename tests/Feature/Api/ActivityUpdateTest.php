<?php

use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->user  = User::factory()->create();
    $this->token = $this->user->createToken('test')->plainTextToken;
});

it('updates activity metadata without re-analysing GPX', function () {
    $activity = Activity::factory()->create(['title' => 'Ancien titre']);

    $this->withToken($this->token)
        ->putJson("/api/activities/{$activity->id}", [
            'title' => 'Nouveau titre',
        ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Nouveau titre');

    $this->assertDatabaseHas('activities', ['id' => $activity->id, 'title' => 'Nouveau titre']);
});

it('re-analyses GPX when a new file is provided', function () {
    $activity = Activity::factory()->create(['gpx_path' => 'gpx/old_trace.gpx']);
    Storage::disk('local')->put('gpx/old_trace.gpx', 'fake old content');

    $newGpxFile = UploadedFile::fake()->createWithContent(
        'new_trace.gpx',
        file_get_contents(base_path('tests/Fixtures/gpx/simple_track.gpx'))
    );

    $this->withToken($this->token)
        ->putJson("/api/activities/{$activity->id}", [
            'gpx_file' => $newGpxFile,
        ])
        ->assertOk();

    Storage::disk('local')->assertMissing('gpx/old_trace.gpx');
    $this->assertDatabaseMissing('activities', ['gpx_path' => 'gpx/old_trace.gpx']);
});

it('returns 404 for unknown activity', function () {
    $this->withToken($this->token)
        ->putJson('/api/activities/999', ['title' => 'Test'])
        ->assertNotFound();
});

it('returns 401 without token', function () {
    $activity = Activity::factory()->create();

    $this->putJson("/api/activities/{$activity->id}", [])
        ->assertUnauthorized();
});

it('recalculates stats for an activity', function () {
    $gpxContent = file_get_contents(base_path('tests/Fixtures/gpx/simple_track.gpx'));
    Storage::disk('local')->put('gpx/trace.gpx', $gpxContent);

    $activity = Activity::factory()->create(['gpx_path' => 'gpx/trace.gpx']);

    $this->withToken($this->token)
        ->postJson("/api/activities/{$activity->id}/recalculate")
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => ['id', 'title', 'segments'],
        ]);
});

it('returns 401 on recalculate without token', function () {
    $activity = Activity::factory()->create();

    $this->postJson("/api/activities/{$activity->id}/recalculate")
        ->assertUnauthorized();
});