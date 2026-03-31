import { defineConfig, mergeConfig } from 'vitest/config';
import viteConfig from './vite.config.js';

export default mergeConfig(
    viteConfig,
    defineConfig({
        test: {
            environment: 'jsdom',
            globals: true,
            setupFiles: [],
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
    }),
);
