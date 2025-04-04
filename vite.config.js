import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        chunkSizeWarningLimit: 2000, // Augmentation de la limite
        rollupOptions: {
            output: {
                manualChunks: {
                    'highlight': ['highlight.js'],
                    'vendor': ['vue', '@inertiajs/vue3'],
                    'markdown': ['markdown-it']
                }
            }
        }
    }
});
