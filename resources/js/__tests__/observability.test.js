import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createApp } from 'vue';
import * as Sentry from '@sentry/vue';
import { dropBreadcrumb, initErrorTracking, stripRequestData } from '@/observability';

vi.mock('@sentry/vue', () => ({
    init: vi.fn(),
    vueIntegration: vi.fn((options) => ({ name: 'Vue', options })),
}));

describe('initErrorTracking', () => {
    beforeEach(() => {
        vi.mocked(Sentry.init).mockClear();
    });

    afterEach(() => {
        vi.unstubAllEnvs();
    });

    it.each([
        ['unset', ''],
        ['blank', '   '],
    ])('does not initialise Sentry when VITE_SENTRY_DSN is %s', (_label, value) => {
        vi.stubEnv('VITE_SENTRY_DSN', value);

        initErrorTracking(createApp({}));

        expect(Sentry.init).not.toHaveBeenCalled();
    });

    it('initialises Sentry with the app, the trimmed DSN, the relay and no collected data', () => {
        vi.stubEnv('VITE_SENTRY_DSN', ' https://key@o1.ingest.de.sentry.io/2 ');
        vi.stubEnv('VITE_SENTRY_ENVIRONMENT', 'production');
        const app = createApp({});

        initErrorTracking(app);

        expect(Sentry.init).toHaveBeenCalledWith({
            app,
            dsn: 'https://key@o1.ingest.de.sentry.io/2',
            environment: 'production',
            integrations: [{ name: 'Vue', options: { attachProps: false } }],
            tracesSampleRate: 0,
            tunnel: { basePath: '/api' },
            beforeSend: stripRequestData,
            beforeBreadcrumb: dropBreadcrumb,
            dataCollection: {
                userInfo: false,
                cookies: false,
                httpHeaders: { request: false, response: false },
                httpBodies: [],
                urlQueryParams: false,
                graphQL: { document: false, variables: false },
                genAI: { inputs: false, outputs: false },
                mcp: { inputs: false, outputs: false },
                databaseQueryData: false,
                queues: false,
                stackFrameVariables: false,
                frameContextLines: 0,
            },
        });
    });

    it('defaults the environment to development', () => {
        vi.stubEnv('VITE_SENTRY_DSN', 'https://key@o1.ingest.de.sentry.io/2');
        vi.stubEnv('VITE_SENTRY_ENVIRONMENT', '');

        initErrorTracking(createApp({}));

        expect(Sentry.init).toHaveBeenCalledWith(expect.objectContaining({ environment: 'development' }));
    });
});

describe('stripRequestData', () => {
    it('keeps the page URL without its query string or fragment, and drops request data and breadcrumbs', () => {
        const event = {
            message: 'boom',
            request: {
                url: 'https://summit-stats.example/activities/3?token=SECRET#map',
                headers: { Authorization: 'Bearer SECRET' },
                query_string: 'token=SECRET',
            },
            breadcrumbs: [{ category: 'xhr', data: { url: '/api/activities?token=SECRET' } }],
        };

        expect(stripRequestData(event)).toEqual({
            message: 'boom',
            request: { url: 'https://summit-stats.example/activities/3' },
        });
    });

    it('leaves an event without request data untouched', () => {
        expect(stripRequestData({ message: 'boom' })).toEqual({ message: 'boom' });
    });
});

describe('dropBreadcrumb', () => {
    it('drops every breadcrumb, which can hold URLs, console output or GPS data', () => {
        expect(dropBreadcrumb()).toBeNull();
    });
});
