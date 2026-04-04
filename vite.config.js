import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/admin/main.jsx',
                'resources/scss/app.scss',
                'resources/scss/frontend.scss',
            ],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@admin': '/resources/js/admin',
            '@modules': '/modules',
        },
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    react: ['react', 'react-dom', 'react-router-dom'],
                    antd: ['antd', '@ant-design/icons'],
                    axios: ['axios'],
                    chonky: ['chonky', 'chonky-icon-fontawesome'],
                    tinymce: ['tinymce', '@tinymce/tinymce-react'],
                    puck: ['@puckeditor/core'],
                },
            },
        },
    },
});
