<?php

use App\Services\Observability\SentryScrubber;
use Sentry\Breadcrumb;
use Sentry\Event;

it('keeps only the method and the URL path of the request, and drops breadcrumbs', function () {
    $event = Event::createEvent()
        ->setRequest([
            'method' => 'POST',
            'url' => 'https://summit-stats.example/api/activities?token=SECRET#map',
            'headers' => ['Authorization' => ['Bearer SECRET']],
            'cookies' => ['session' => 'SECRET'],
            'query_string' => 'token=SECRET',
            'data' => ['gpx' => '<trkpt lat="45.1" lon="6.2"/>'],
            'env' => ['REMOTE_ADDR' => '203.0.113.7'],
        ])
        ->setBreadcrumb([new Breadcrumb(Breadcrumb::LEVEL_INFO, Breadcrumb::TYPE_DEFAULT, 'query', 'select SECRET')]);

    $scrubbed = SentryScrubber::beforeSend($event);

    expect($scrubbed->getRequest())->toBe([
        'method' => 'POST',
        'url' => 'https://summit-stats.example/api/activities',
    ])->and($scrubbed->getBreadcrumbs())->toBe([]);
});

it('leaves an event without request data untouched', function () {
    $event = Event::createEvent();

    expect(SentryScrubber::beforeSend($event)->getRequest())->toBe([]);
});

it('drops every breadcrumb', function () {
    expect(SentryScrubber::dropBreadcrumb(new Breadcrumb(Breadcrumb::LEVEL_INFO, Breadcrumb::TYPE_DEFAULT, 'log')))->toBeNull();
});
