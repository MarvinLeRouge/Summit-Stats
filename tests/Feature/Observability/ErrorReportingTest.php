<?php

use App\Exceptions\GpxParseException;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;

beforeEach(function () {
    $this->events = new ArrayObject;

    $transport = new class($this->events) implements TransportInterface
    {
        public function __construct(private ArrayObject $events) {}

        public function send(Event $event): Result
        {
            $this->events->append($event);

            return new Result(ResultStatus::success(), $event);
        }

        public function close(?int $timeout = null): Result
        {
            return new Result(ResultStatus::success());
        }
    };

    SentrySdk::getCurrentHub()->bindClient(
        ClientBuilder::create(['dsn' => 'https://key@o1.ingest.de.sentry.io/2'])
            ->setTransport($transport)
            ->getClient(),
    );

    // POST: the SPA catch-all in routes/web.php answers every GET
    Route::post('/api/test-server-error', fn () => throw new RuntimeException('boom'));
    Route::post('/api/test-gpx-error', fn () => throw GpxParseException::insufficientPoints());
});

afterEach(function () {
    SentrySdk::getCurrentHub()->bindClient(ClientBuilder::create()->getClient());
});

it('reports an unhandled exception to Sentry', function () {
    $this->postJson('/api/test-server-error')->assertServerError();

    expect($this->events)->toHaveCount(1)
        ->and($this->events[0]->getExceptions()[0]->getValue())->toBe('boom');
});

it('does not report an invalid GPX file (422)', function () {
    $this->postJson('/api/test-gpx-error')->assertUnprocessable();

    expect($this->events)->toHaveCount(0);
});

it('does not report an unauthenticated request (401)', function () {
    $this->getJson('/api/activities')->assertUnauthorized();

    expect($this->events)->toHaveCount(0);
});

it('does not report a missing activity (404)', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/activities/999')->assertNotFound();

    expect($this->events)->toHaveCount(0);
});
