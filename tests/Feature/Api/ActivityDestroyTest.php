<?php

use App\Models\Activity;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->user  = User::factory()->create();
    $this->token = $this->user->createToken('test')->plainTextToken;
});

it('deletes activity and its segments and GPX file', function () {
    $activity = Activity::factory()->create(['gpx_path' => 'gpx/trace.gpx']);
    Segment::factory()->count(3)->create(['activity_id' => $activity->id]);
    Storage::disk('local')->put('gpx/trace.gpx', 'fake gpx content');

    $this->withToken($this->token)
        ->deleteJson("/api/activities/{$activity->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    $this->assertDatabaseMissing('segments', ['activity_id' => $activity->id]);
    Storage::disk('local')->assertMissing('gpx/trace.gpx');
});

it('returns 404 for unknown activity', function () {
    $this->withToken($this->token)
        ->deleteJson('/api/activities/999')
        ->assertNotFound();
});

it('returns 401 without token', function () {
    $activity = Activity::factory()->create();

    $this->deleteJson("/api/activities/{$activity->id}")
        ->assertUnauthorized();
});