import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react'; // Added

export default defineConfig({
  plugins: [
    laravel({
      input: [
        'resources/sass/app.scss',
        'resources/js/app.jsx', // Changed extension
      ],
      refresh: true,
    }),
    react(), // Added
  ],
  css: { // Added
    preprocessorOptions: { // Added
      scss: { // Added
        quietDeps: true, // Added: Suppress warnings from node_modules (like Bootstrap)
      }, // Added
    }, // Added
  }, // Added
  build: {
    outDir: 'public/build',
  },
});
