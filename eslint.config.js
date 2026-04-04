import js from '@eslint/js';
import reactHooks from 'eslint-plugin-react-hooks';
import reactPlugin from 'eslint-plugin-react';

export default [
    js.configs.recommended,
    {
        files: [
            'resources/js/admin/**/*.{js,jsx}',
            'modules/**/Resources/js/**/*.{js,jsx}',
            'plugins/**/resources/js/**/*.{js,jsx}',
        ],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            parserOptions: {
                ecmaFeatures: {
                    jsx: true,
                },
            },
            globals: {
                document: 'readonly',
                File: 'readonly',
                FormData: 'readonly',
                window: 'readonly',
            },
        },
        plugins: {
            react: reactPlugin,
            'react-hooks': reactHooks,
        },
        rules: {
            ...reactPlugin.configs.recommended.rules,
            ...reactHooks.configs.recommended.rules,
            'react/react-in-jsx-scope': 'off',
            'react/prop-types': 'off',
            'no-undef': 'error',
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
    },
];
