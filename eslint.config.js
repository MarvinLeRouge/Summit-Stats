import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import prettier from 'eslint-config-prettier';
import globals from 'globals';

export default [
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    prettier,
    {
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.es2021,
            },
        },
        rules: {
            'vue/multi-word-component-names':   'off',
            'vue/no-unused-vars':               'warn',
            'vue/attributes-order':             'warn',
            'vue/first-attribute-linebreak':    'off',
            'vue/require-default-prop':         'off',
            'no-unused-vars':                   'warn',
            'no-console': ['warn', { allow: ['error', 'warn'] }],
        },
    },
    {
        ignores: ['public/**', 'vendor/**', 'node_modules/**'],
    },
];