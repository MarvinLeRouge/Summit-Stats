import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: { '@': '/resources/js' },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        exclude: ['**/node_modules/**', 'e2e/**'],
        coverage: {
            provider: 'v8',
            include: ['resources/js/**'],
            exclude: [
                // Entry points — no testable logic in isolation
                'resources/js/app.js',
                'resources/js/bootstrap.js',
                // Pages — covered by E2E tests
                'resources/js/pages/**',
                // Router module — guard logic is tested directly in guard.test.js
                'resources/js/router/**',
                // Components depending on Leaflet / Chart.js / SSE — covered by E2E tests
                'resources/js/components/BaseChart.vue',
                'resources/js/components/ElevationProfile.vue',
                'resources/js/components/GpxUploadForm.vue',
                'resources/js/components/ProgressionChart.vue',
                'resources/js/components/TrackMap.vue',
                // App shell
                'resources/js/App.vue',
            ],
            reporter: ['text', 'lcov'],
            reportsDirectory: 'coverage-frontend',
        },
    },
});
