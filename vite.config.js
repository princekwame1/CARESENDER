import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Poppins', { weights: [400, 500, 600, 700] }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        // Leaflet is split into its own chunk and only fetched when a map is visible.
        cssCodeSplit: true,
        reportCompressedSize: true,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
