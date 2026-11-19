<?php

use App\Services\Observability\SentryScrubber;

it('collects no personal data, no request data and no traces', function () {
    $sentry = require base_path('config/sentry.php');

    expect($sentry)
        ->send_default_pii->toBeFalse()
        ->traces_sample_rate->toBe(0.0)
        ->profiles_sample_rate->toBeNull()
        ->enable_logs->toBeFalse()
        ->max_request_body_size->toBe('never')
        ->before_send->toBe([SentryScrubber::class, 'beforeSend'])
        ->before_breadcrumb->toBe([SentryScrubber::class, 'dropBreadcrumb'])
        ->data_collection->toBe([
            'user_info' => false,
            'cookies' => false,
            'http_headers' => ['request' => false, 'response' => false],
            'http_bodies' => [],
            'url_query_params' => false,
            'gen_ai' => ['inputs' => false, 'outputs' => false],
            'database_query_data' => false,
            'queues' => false,
            'stack_frame_variables' => false,
            'frame_context_lines' => 0,
        ]);
});
