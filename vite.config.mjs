import { resolve } from 'node:path';

import { defineConfig } from 'vite';

const root = resolve(import.meta.dirname, 'src/web/assets/field');

export default defineConfig({
    root,
    input: resolve(root, 'src/many-to-many.js'),
    build: {
        outDir: resolve(root, 'dist'),
        emptyOutDir: true,
        minify: 'oxc',
        sourcemap: true,
        target: 'es2015',
        rolldownOptions: {
            output: {
                codeSplitting: false,
                entryFileNames: '[name].js',
                format: 'iife',
            },
        },
    },
});
