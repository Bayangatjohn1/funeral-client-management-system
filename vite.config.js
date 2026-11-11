import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ command }) => ({
    base: command === 'build' ? './' : undefined,
    server: { watch: { ignored: ['**/storage/framework/views/**'] } },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/system-admin-dashboard.css', 'resources/js/system-admin-dashboard.js', 'resources/js/service-brochure.js'],
            refresh: true,
        }),
    ],
}));
