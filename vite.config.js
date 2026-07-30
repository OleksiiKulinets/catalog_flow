import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['packages\CatFlow\Admin\src\Resources\css\app.css', 'packages\CatFlow\Admin\src\Resources\js\app.js'],
            refresh: true,
        }),
    ],
});
