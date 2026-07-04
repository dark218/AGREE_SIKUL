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
        // Relève le seuil d'alerte (les vrais gros chunks sont désormais isolés ci-dessous)
        chunkSizeWarningLimit: 700,
        rollupOptions: {
            external: [
                /^\/assets\//,
                /^\/backend\//,
                /^\/images\//,
            ],
            output: {
                // Isole les grosses dépendances dans leurs propres chunks :
                // chargées une seule fois puis mises en cache par le navigateur,
                // au lieu d'être fusionnées dans app.js / les pages.
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('firebase') || id.includes('@firebase')) return 'vendor-firebase';
                    if (id.includes('xlsx')) return 'vendor-xlsx';
                    if (id.includes('jspdf')) return 'vendor-jspdf';
                    if (id.includes('html2canvas')) return 'vendor-html2canvas';
                    if (id.includes('chart.js') || id.includes('vue-chartjs')) return 'vendor-charts';
                    if (id.includes('leaflet')) return 'vendor-leaflet';
                    if (id.includes('select2') || id.includes('jquery')) return 'vendor-jquery';
                },
            },
        }
    }
});