import js from '@eslint/js';

export default [
    {
        files: ['**/*.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                window: 'readonly',
                document: 'readonly',
                console: 'readonly',
                M: 'readonly',
                Y: 'readonly',
                L: 'readonly',
                define: 'readonly',
                require: 'readonly'
            }
        },
        rules: {
            ...js.configs.recommended.rules,
            'no-unused-vars': ['warn', { 'args': 'none' }],
            'no-console': 'off',
            'comma-dangle': ['error', 'never'],
            'eol-last': 'error',
            'indent': ['error', 4, { 'SwitchCase': 1 }],
            'linebreak-style': ['error', 'unix'],
            'max-len': ['error', { 'code': 120, 'ignoreUrls': true }],
            'no-trailing-spaces': 'error',
            'quotes': ['error', 'single', { 'avoidEscape': true }],
            'semi': ['error', 'always'],
            'space-before-function-paren': ['error', 'never'],
            'no-undef': 'error',
            'prefer-const': 'error',
            'no-var': 'error'
        }
    },
    {
        files: ['amd/src/**/*.js'],
        languageOptions: {
            sourceType: 'module',
            ecmaVersion: 2022
        }
    },
    {
        files: ['amd/build/**/*.js'],
        rules: {
            'max-len': 'off',
            'no-unused-vars': 'off'
        }
    },
    {
        ignores: [
            'node_modules/**',
            'amd/build/**/*.min.js',
            'thirdparty/**',
            'coverage/**'
        ]
    }
];
