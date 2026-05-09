import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path'; // <-- AJOUTER CECI

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/css/app.css'
            ],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            // ATTENTION : les alias plus spécifiques doivent être déclarés AVANT le générique.
            // Sur Linux (Docker/Dokploy) le FS est case-sensitive : `composables` (lowercase)
            // n'existe pas dans le repo, c'est `Composables` (cap). On redirige les deux pour
            // éviter d'avoir à patcher 200+ imports.
            '@/composables': path.resolve(__dirname, 'resources/js/Composables'),
            '@/Composables': path.resolve(__dirname, 'resources/js/Composables'),
            '@': path.resolve(__dirname, 'resources/js'),
            '@modules': path.resolve(__dirname, 'Modules'),
        }
    },
    server: {
        host: 'localhost',
        port: 5173,
        cors: true,
        hmr: {
            host: 'localhost',
            port: 5173,
        }
    },
    build: {
        rollupOptions: {
            external: [
                /^\/assets\//,
                /^\/backend\//,
                /^\/images\//,
            ]
        }
    }
});