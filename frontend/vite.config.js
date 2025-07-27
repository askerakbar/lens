// frontend/vite.config.js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import { resolve } from 'path'
import { configDefaults } from 'vitest/config'

/**
 * Laminas Lens: A Monitoring Plugin for Laminas Apps
 * https://claude.ai/chat/d30fbee8-9e1c-4786-90cd-13ad6b7f5fb0
 */
export default defineConfig(({ mode }) => {
  const isDevelopment = mode === 'development'
  
  // Determine output directory based on environment variable or mode
  const getOutDir = () => {
    // Check for environment variable to override output
    if (process.env.LENS_OUTPUT === 'main') {
        return '/Users/askerakbar/Sites/laminas/public/laminas-lens'

      return resolve(__dirname, '../../../public/laminas-lens')
    }

    if (process.env.LENS_OUTPUT === 'local') {
      return resolve(__dirname, '../public/laminas-lens')
    }
    
    // Default behavior based on mode
    return isDevelopment 
      ? resolve(__dirname, '../../../public/laminas-lens')  // Main public for development
      : resolve(__dirname, '../public/laminas-lens')        // Local for production builds
  }
  
  const outDir = getOutDir()
  
  console.log(`🔍 Laminas Lens building to: ${outDir}`)
  
  return {
    plugins: [react()],
    build: {
      outDir,
      emptyOutDir: true,
      assetsDir: 'assets',
      base: '/laminas-lens/',
      // Watch mode for development
      watch: isDevelopment ? {
        include: 'src/**',
        exclude: ['node_modules/**', 'dist/**', '../public/**', '../../../public/**']
      } : null,
      // Development optimizations
      minify: isDevelopment ? false : 'esbuild',
      sourcemap: isDevelopment ? true : false,
      rollupOptions: {
        input: {
          main: resolve(__dirname, 'index.html'),
        },
        output: {
          entryFileNames: 'assets/app.js',
          assetFileNames: 'assets/app.[ext]',
        },
        inlineDynamicImports: true,
      }
    },
    server: {
      port: 5173,
      host: '0.0.0.0'
    },
    test: {
      environment: 'jsdom',
      globals: true,
      setupFiles: [],
      exclude: [...configDefaults.exclude, 'node_modules', 'dist'],
      coverage: {
        reporter: ['text', 'html'],
      },
    },
  }
})