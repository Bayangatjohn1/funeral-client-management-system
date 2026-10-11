import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/system-admin-dashboard.css', 'resources/js/system-admin-dashboard.js'],
            refresh: true,
        }),
    ],
});
