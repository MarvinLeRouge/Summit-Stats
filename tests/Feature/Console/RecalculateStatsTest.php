<?php

use App\Models\Activity;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

it('recalculates stats for all activities', function () {
    $gpxContent = file_get_contents(base_path('tests/Fixtures/gpx/simple_track.gpx'));
    Storage::disk('local')->put('gpx/trace.gpx', $gpxContent);

    $activity = Activity::factory()->create(['gpx_path' => 'gpx/trace.gpx']);
    Segment::factory()->count(2)->create(['activity_id' => $activity->id]);

    $this->artisan('stats:recalculate')
        ->assertSuccessful();

    // Les anciens segments ont été supprimés et recréés
    expect(Activity::find($activity->id)->segments)->not->toBeEmpty();
});

it('recalculates stats for a single activity with --id option', function () {
    $gpxContent = file_get_contents(base_path('tests/Fixtures/gpx/simple_track.gpx'));
    Storage::disk('local')->put('gpx/trace.gpx', $gpxContent);

    $activity = Activity::factory()->create(['gpx_path' => 'gpx/trace.gpx']);

    $this->artisan('stats:recalculate', ['--id' => $activity->id])
        ->assertSuccessful();
});

it('warns when no activity found with given id', function () {
    $this->artisan('stats:recalculate', ['--id' => 999])
        ->assertSuccessful()
        ->expectsOutputToContain('Aucune activité');
});

it('reports error when GPX file is missing', function () {
    $activity = Activity::factory()->create(['gpx_path' => 'gpx/missing.gpx']);

    $this->artisan('stats:recalculate')
        ->assertFailed();
});