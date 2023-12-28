import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        react(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',

                //client
                'resources/css/client/app.scss',
                'resources/css/client/custom.css',
                'resources/js/client/App.jsx',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js/client',
            '@css': '/resources/css/client',
            '@vendor': '/public/vendor',
            'ziggy': '/vendor/tightenco/ziggy/dist/index.es.js'
        },
    },
});
