import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  root: './',
  build: {
    outDir: 'dist',
    emptyOutDir: false,
    manifest: 'manifest.json',
    // Enable CSS code splitting for better performance
    cssCodeSplit: true,
    // Set chunk size limits to encourage splitting
    rollupOptions: {
      input: {
        main: './src/js/main.js'
      },
      output: {
        // Disable hashing for JS files
        entryFileNames: 'assets/[name].js',
        // Disable hashing for CSS files
        assetFileNames: (assetInfo) => {
          if (assetInfo.names && assetInfo.names[0] && assetInfo.names[0].endsWith('.css')) {
            return 'assets/css/[name].css';
          }
          // Keep hash for other assets like images, fonts, etc.
          return 'assets/[name]-[hash][extname]';
        },
        // Disable hashing for chunk files
        chunkFileNames: 'assets/js/[name].js',
        // Optional: Manual chunk splitting for better caching
        manualChunks: (id) => {
          // Vendor libraries
          if (id.includes('node_modules')) {
            // Large libraries get their own chunks
            // if (id.includes('react')) return 'react-vendor';
            // Small vendor libs grouped together
            return 'vendor';
          }
          
          // Split by feature/directory
          if (id.includes('/src/js/hooks/')) return 'hooks';
          if (id.includes('/src/js/lib/')) return 'lib';
        }
      }
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
      // Additional useful aliases
    },
  },
});