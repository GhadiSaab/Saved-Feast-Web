const { defineConfig } = require('vite');
const react = require('@vitejs/plugin-react');

module.exports = defineConfig(async () => {
  const laravel = await import('laravel-vite-plugin');
  
  return {
    plugins: [
      laravel.default({
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
  };
});
