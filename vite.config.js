import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: [
                '**/storage/framework/**',
                '**/storage/logs/**',
                '**/bootstrap/cache/**',
                '**/vendor/**',
                '**/node_modules/.vite/**',
            ],
        },
    },
    optimizeDeps: {
        exclude: [
            'datatables.net',
            'datatables.net-dt',
            'datatables.net-vue3',
            'datatables.net-responsive',
            'datatables.net-responsive-dt',
        ],
    },
});
