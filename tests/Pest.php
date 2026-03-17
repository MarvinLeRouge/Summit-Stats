<?php

use App\Services\ActivityService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

function postActivityWithGpx(mixed $test, string $gpxPath, array $metadata = []): array
{
    $file = new UploadedFile($gpxPath, basename($gpxPath), 'application/gpx+xml', null, true);

    $service = app(ActivityService::class);
    $activity = $service->store(
        array_merge([
            'title' => 'Test Activity',
            'type' => 'randonnee',
            'environment' => 'montagne',
            'date' => '2024-03-15',
            'comment' => null,
        ], $metadata),
        $file,
    );

    return ['activity' => $activity->toArray()];
}
