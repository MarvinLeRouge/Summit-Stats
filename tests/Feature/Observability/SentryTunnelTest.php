<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

const FRONTEND_DSN = 'https://key@o1.ingest.de.sentry.io/2';
// Path @sentry/core 11 computes for FRONTEND_DSN with tunnel: { basePath: '/api' } (FNV-1a 32, base 36)
const TUNNEL_PATH = '/api/6lxkyw';

function envelope(string $dsn = FRONTEND_DSN): string
{
    return json_encode(['event_id' => 'abc', 'dsn' => $dsn])."\n".'{"type":"event"}'."\n".'{"message":"boom"}';
}

function postEnvelope(mixed $test, string $body, string $path = TUNNEL_PATH): mixed
{
    return $test->call('POST', $path, [], [], [], ['CONTENT_TYPE' => 'text/plain;charset=UTF-8'], $body);
}

beforeEach(function () {
    config(['services.sentry_tunnel.frontend_dsn' => FRONTEND_DSN]);
});

it('forwards an envelope for the frontend DSN to its Sentry project, without authentication', function () {
    Http::fake(['o1.ingest.de.sentry.io/*' => Http::response('{"id":"abc"}', 200)]);
    $body = envelope();

    postEnvelope($this, $body)->assertOk()->assertContent('{"id":"abc"}');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://o1.ingest.de.sentry.io/api/2/envelope/'
        && $request->body() === $body
        && $request->hasHeader('Content-Type', 'application/x-sentry-envelope'));
});

it('passes the Sentry status through', function () {
    Http::fake(['*' => Http::response('', 429)]);

    postEnvelope($this, envelope())->assertStatus(429);
});

it('rejects an envelope for another DSN', function () {
    Http::fake();

    postEnvelope($this, envelope('https://other@o9.ingest.de.sentry.io/9'))->assertForbidden();

    Http::assertNothingSent();
});

it('rejects an envelope with a malformed header', function (string $body) {
    Http::fake();

    postEnvelope($this, $body)->assertBadRequest();

    Http::assertNothingSent();
})->with([
    'not JSON' => "garbage\n{}",
    'no dsn' => '{"event_id":"abc"}'."\n{}",
    'empty' => '',
]);

it('rejects an envelope above 1 MB', function () {
    Http::fake();

    postEnvelope($this, envelope().str_repeat('x', 1024 * 1024))->assertStatus(413);

    Http::assertNothingSent();
});

it('answers 404 and logs a warning when the path matches no configured DSN', function () {
    Http::fake();
    Log::spy();

    postEnvelope($this, envelope(), '/api/zzzzzz')->assertNotFound();

    Http::assertNothingSent();
    Log::shouldHaveReceived('warning')->once()->with(
        'Sentry tunnel path matches no configured DSN, check that SENTRY_FRONTEND_DSN equals VITE_SENTRY_DSN',
        ['path' => 'zzzzzz'],
    );
});

it('answers 404 when no frontend DSN is configured', function () {
    config(['services.sentry_tunnel.frontend_dsn' => null]);
    Http::fake();

    postEnvelope($this, envelope())->assertNotFound();

    Http::assertNothingSent();
});

it('answers 502 when Sentry is unreachable', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    postEnvelope($this, envelope())->assertStatus(502);
});

it('keeps the login route reachable', function () {
    $this->postJson('/api/login', [])->assertUnprocessable();
});
