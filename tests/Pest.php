<?php

use Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

function postActivityWithGpx(mixed $test, string $gpxPath, array $metadata = []): array
{
    $file = new \Illuminate\Http\UploadedFile($gpxPath, basename($gpxPath), 'application/gpx+xml', null, true);

    $service  = app(\App\Services\ActivityService::class);
    $activity = $service->store(
        array_merge([
            'title'       => 'Test Activity',
            'type'        => 'randonnee',
            'environment' => 'montagne',
            'date'        => '2024-03-15',
            'comment'     => null,
        ], $metadata),
        $file,
    );

    return ['activity' => $activity->toArray()];
}