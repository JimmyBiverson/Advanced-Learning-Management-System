import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { createModuleViteAliases } from './vite-module-aliases';

export default defineConfig({
   plugins: [
      laravel({
         input: ['resources/css/app.css', 'resources/js/app.tsx'],
         refresh: true,
      }),
      inertia(),
      react({
         // Section templates under page-jsx are invoked as plain functions from
         // editor palette data (e.g. Hero1()) during module init; the React Compiler
         // assumes components only run under React's dispatcher, which breaks that pattern.
         babel: (id) => ({
            plugins: id.includes('page-jsx')
               ? []
               : ['babel-plugin-react-compiler'],
         }),
      }),
      tailwindcss(),
      wayfinder({
         formVariants: true,
      }),
   ],
   resolve: {
      dedupe: ['react', 'react-dom'],
      tsconfigPaths: true,
      alias: createModuleViteAliases(),
   },
   esbuild: {
      drop:
         process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
   } as any,
   build: {
      cssCodeSplit: true,
      minify: true,
      sourcemap: false,
      reportCompressedSize: false,
      chunkSizeWarningLimit: 1000,
      // rollupOptions: {
      //    output: {
      //       manualChunks(id) {
      //          if (id.includes('node_modules')) {
      //             if (id.includes('@inertiajs')) {
      //                return 'inertia';
      //             }

      //             if (id.includes('react-dom')) {
      //                return 'react-dom';
      //             }

      //             if (id.includes('react') || id.includes('scheduler')) {
      //                return 'react';
      //             }

      //             if (id.includes('ziggy-js') || id.includes('wayfinder')) {
      //                return 'routing';
      //             }

      //             if (id.includes('@tiptap') || id.includes('prosemirror')) {
      //                return 'editor';
      //             }

      //             if (id.includes('@codemirror') || id.includes('@lezer') || id.includes('codemirror')) {
      //                return 'codemirror';
      //             }

      //             if (id.includes('@radix-ui') || id.includes('radix-ui')) {
      //                return 'ui-radix';
      //             }

      //             if (id.includes('lucide-react')) {
      //                return 'icons';
      //             }

      //             if (id.includes('@zoom')) {
      //                return 'zoom';
      //             }

      //             if (id.includes('jspdf') || id.includes('html2canvas')) {
      //                return 'pdf';
      //             }

      //             if (id.includes('recharts') || id.includes('d3-')) {
      //                return 'charts';
      //             }

      //             return 'vendor';
      //          }
      //       },
      //    },
      // },
   },
});
