import {
    defineConfig
} from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from "@tailwindcss/vite";
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/terrain-init.js', 'resources/css/filament/commerce/theme.css', 'resources/css/filament/customer/theme.css', 'resources/css/filament/core/theme.css', 'resources/css/filament/rh/theme.css', 'resources/css/filament/chantier/theme.css', 'resources/css/filament/gpao/theme.css', 'resources/css/filament/salarie/theme.css', 'resources/css/filament/technicien/theme.css', 'resources/css/filament/terrain/theme.css'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
        VitePWA({
            strategies: 'injectManifest',
            srcDir: 'resources/js',
            filename: 'sw.js',
            outDir: 'public',
            injectManifest: {
                injectionPoint: undefined
            },
            manifest: {
                name: 'Batistack ERP',
                short_name: 'Batistack',
                description: "L'ERP complet de gestion de chantiers pour Batistack.",
                theme_color: '#f97316',
                background_color: '#0f172a',
                display: 'standalone',
                orientation: 'portrait-primary',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: '/images/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/images/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any maskable', // Ensure logo is within inner 80% safe zone
                    }
                ]
            }
        }),
    ],
    server: {
        cors: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
