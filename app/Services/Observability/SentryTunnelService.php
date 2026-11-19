<?php

namespace App\Services\Observability;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Same-origin relay for browser Sentry events, so ad-blockers and tracking protection cannot
 * drop them. Mirrors `tunnel: { basePath: '/api' }` of @sentry/vue: the browser posts to
 * /api/<hash of its DSN>, and only the configured frontend DSN is relayed.
 */
class SentryTunnelService
{
    /**
     * Returns the configured frontend DSN when its tunnel path is the given one.
     */
    public function dsnForPath(string $path): ?string
    {
        $dsn = trim((string) config('services.sentry_tunnel.frontend_dsn'));

        return $dsn !== '' && self::tunnelPath($dsn) === $path ? $dsn : null;
    }

    /**
     * Tunnel path segment @sentry/core derives from a DSN: FNV-1a 32-bit, in base 36.
     * Keep in sync with getTunnelPath() in @sentry/core (stable across SDK versions).
     */
    public static function tunnelPath(string $dsn): string
    {
        return base_convert(hash('fnv1a32', $dsn), 16, 36);
    }

    /**
     * Returns the DSN declared in the envelope header (its first line), or null if malformed.
     */
    public function envelopeDsn(string $envelope): ?string
    {
        $header = json_decode(strtok($envelope, "\n") ?: '', true);

        return is_array($header) && is_string($header['dsn'] ?? null) ? $header['dsn'] : null;
    }

    /**
     * Forwards the raw envelope to the ingest endpoint of the DSN's Sentry project.
     *
     * @throws ConnectionException when Sentry is unreachable
     */
    public function forward(string $dsn, string $envelope): Response
    {
        $parts = parse_url($dsn);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $projectId = basename($parts['path'] ?? '');

        return Http::withBody($envelope, 'application/x-sentry-envelope')
            ->timeout(5)
            ->post("{$parts['scheme']}://{$parts['host']}{$port}/api/{$projectId}/envelope/");
    }
}
