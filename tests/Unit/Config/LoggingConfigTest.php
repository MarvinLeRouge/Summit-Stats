<?php

use Illuminate\Support\Facades\Log;
use Monolog\Formatter\JsonFormatter;

it('writes one JSON object per line on the stderr channel when LOG_STDERR_FORMATTER is JsonFormatter', function () {
    putenv('LOG_STDERR_FORMATTER='.JsonFormatter::class);
    unset($_ENV['LOG_STDERR_FORMATTER'], $_SERVER['LOG_STDERR_FORMATTER']);

    try {
        $stderr = (require base_path('config/logging.php'))['channels']['stderr'];
    } finally {
        putenv('LOG_STDERR_FORMATTER');
    }

    $file = tempnam(sys_get_temp_dir(), 'log');
    $stderr['handler_with']['stream'] = $file;

    Log::build($stderr)->warning('Elevation API call failed', ['error' => 'timeout']);

    $line = json_decode(trim(file_get_contents($file)), true, flags: JSON_THROW_ON_ERROR);
    unlink($file);

    expect($line)
        ->message->toBe('Elevation API call failed')
        ->level_name->toBe('WARNING')
        ->context->toBe(['error' => 'timeout']);
});
