<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Observability\SentryTunnelService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SentryTunnelController extends Controller
{
    private const MAX_ENVELOPE_BYTES = 1024 * 1024;

    public function __construct(
        private readonly SentryTunnelService $tunnel,
    ) {}

    /**
     * Relays a browser Sentry envelope to Sentry: 404 for an unknown tunnel path, 413 above 1 MB,
     * 400 for a malformed envelope header, 403 for a DSN other than the frontend one,
     * 502 when Sentry is unreachable, otherwise Sentry's own status.
     */
    public function __invoke(Request $request, string $tunnelPath): Response
    {
        $dsn = $this->tunnel->dsnForPath($tunnelPath);

        if ($dsn === null) {
            if (filled(config('services.sentry_tunnel.frontend_dsn'))) {
                // The browser DSN is baked in at build time: a mismatch makes every frontend event 404
                Log::warning(
                    'Sentry tunnel path matches no configured DSN, check that SENTRY_FRONTEND_DSN equals VITE_SENTRY_DSN',
                    ['path' => $tunnelPath],
                );
            }

            abort(404);
        }

        $envelope = $request->getContent();

        if (strlen($envelope) > self::MAX_ENVELOPE_BYTES) {
            abort(413);
        }

        $envelopeDsn = $this->tunnel->envelopeDsn($envelope);

        if ($envelopeDsn === null) {
            abort(400);
        }

        if ($envelopeDsn !== $dsn) {
            abort(403);
        }

        try {
            $sentry = $this->tunnel->forward($dsn, $envelope);
        } catch (ConnectionException) {
            abort(502);
        }

        return response($sentry->body(), $sentry->status());
    }
}
