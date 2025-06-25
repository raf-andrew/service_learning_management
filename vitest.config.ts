import { defineConfig } from 'vitest/config'
import { resolve } from 'path'

export default defineConfig({
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./tests/setup.ts'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html', 'lcov'],
      reportsDirectory: '.reports/frontend/coverage',
      exclude: [
        'node_modules/',
        'tests/',
        '**/*.d.ts',
        '**/*.config.*',
        '**/vendor/**',
        '**/*.test.*',
        '**/*.spec.*',
        '**/coverage/**',
        '**/.reports/**',
        '**/dist/**',
        '**/build/**'
      ],
      include: [
        'resources/js/**/*.{js,ts,vue}',
        'src/**/*.{js,ts,vue}',
        '.wireframe/resources/js/**/*.{js,ts,vue}',
        'app/Services/**/*.ts'
      ],
      thresholds: {
        global: {
          branches: 80,
          functions: 80,
          lines: 80,
          statements: 80
        }
      }
    },
    include: [
      'tests/Frontend/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'tests/Unit/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'tests/Integration/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'tests/Functional/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'tests/Sanity/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'tests/Chaos/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'tests/AI/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'tests/MCP/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'tests/Security/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'tests/E2E/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'resources/js/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'src/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      '.wireframe/resources/js/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}',
      'app/Services/**/*.{test,spec}.{js,mjs,cjs,ts,mts,cts,jsx,tsx}'
    ],
    exclude: [
      'node_modules',
      'dist',
      '.idea',
      '.git',
      '.cache',
      'vendor',
      'storage',
      'bootstrap/cache'
    ],
    testTimeout: 10000,
    hookTimeout: 10000,
    teardownTimeout: 10000
  },
  resolve: {
    alias: {
      '@': resolve(__dirname, './resources/js'),
      '~': resolve(__dirname, './resources'),
      '@wireframe': resolve(__dirname, './.wireframe/resources/js'),
      '@services': resolve(__dirname, './app/Services'),
      '@models': [resolve(__dirname, './src/models'), resolve(__dirname, './app/Models')],
      '@tests': resolve(__dirname, './tests')
    }
  }
}) 