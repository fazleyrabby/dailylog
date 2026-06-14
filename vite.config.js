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
                bunny('IBM Plex Sans', {
                    weights: [300, 400, 500, 600, 700],
                }),
                bunny('IBM Plex Mono', {
                    weights: [300, 400, 500, 600, 700],
                }),
                // Reading / editorial typeface for long-form content surfaces.
                bunny('Newsreader', {
                    weights: [400, 500, 600, 700],
                    styles: ['normal', 'italic'],
                }),
                // Technical typeface for code blocks and technical content.
                bunny('JetBrains Mono', {
                    weights: [400, 500, 700],
                }),
                // Fantasy display typeface used by the Quest Mode theme.
                bunny('Cinzel', {
                    weights: [500, 600, 700],
                }),
                // Pixel typefaces used by the Retro 8-Bit theme.
                bunny('VT323', {
                    weights: [400],
                }),
                bunny('Press Start 2P', {
                    weights: [400],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
