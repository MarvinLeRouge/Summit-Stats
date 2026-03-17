<?php

use Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

function postActivityWithGpx(mixed $test, string $gpxPath, array $metadata = []): array
{
    $file = new \Illuminate\Http\UploadedFile($gpxPath, basename($gpxPath), 'application/gpx+xml', null, true);

    $response = $test->post('/api/activities', array_merge([
        'title'       => 'Test Activity',
        'type'        => 'randonnee',
        'environment' => 'montagne',
        'date'        => '2024-03-15',
        'gpx_file'    => $file,
    ], $metadata));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');

    // Parser le stream SSE pour extraire l'événement 'done'
    $body  = $response->streamedContent();
    $lines = explode("\n", $body);
    $data  = null;

    foreach ($lines as $i => $line) {
        if (trim($line) === 'event: done' && isset($lines[$i + 1])) {
            $data = json_decode(str_replace('data: ', '', $lines[$i + 1]), true);
            break;
        }
    }

    return $data ?? [];
}