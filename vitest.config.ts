import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  test: {
    environment: 'jsdom',
    setupFiles: ['./resources/js/__tests__/setup.ts'],
    globals: true,
    css: true,
  },
});
