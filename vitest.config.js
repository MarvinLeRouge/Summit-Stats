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
        coverage: {
            provider: 'v8',
            include: ['resources/js/**'],
            exclude: [
                'resources/js/app.js',
                'resources/js/bootstrap.js',
                'resources/js/pages/**',
            ],
            reporter: ['text', 'lcov'],
            reportsDirectory: 'coverage-frontend',
        },
    },
});
