import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { readdirSync } from 'node:fs';

// One entry per invitation template; the Blade page loads the one its invitation uses.
const templates = readdirSync('resources/js/viewer/templates')
    .map((name) => `resources/js/viewer/templates/${name}/index.js`);

export default defineConfig({
    plugins: [
        laravel({
            input: templates,
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
