<?php

namespace App\Services\Observability;

use Sentry\Breadcrumb;
use Sentry\Event;

/**
 * Safety net on top of the `data_collection` options in config/sentry.php: whatever an SDK
 * integration attaches, only stack traces, the request method and the URL path reach Sentry.
 * GPX uploads carry home locations, so request data is never sent.
 */
class SentryScrubber
{
    /**
     * Keeps only the method and the URL path of the request, and drops breadcrumbs.
     */
    public static function beforeSend(Event $event): Event
    {
        $request = $event->getRequest();

        if ($request !== []) {
            $event->setRequest(array_filter([
                'method' => $request['method'] ?? null,
                'url' => isset($request['url']) ? preg_split('/[?#]/', $request['url'])[0] : null,
            ]));
        }

        return $event->setBreadcrumb([]);
    }

    /**
     * Breadcrumbs record logs, SQL queries and HTTP calls, which can carry coordinates or tokens.
     */
    public static function dropBreadcrumb(Breadcrumb $breadcrumb): ?Breadcrumb
    {
        return null;
    }
}
