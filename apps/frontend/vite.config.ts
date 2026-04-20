import path from 'node:path'
import { fileURLToPath } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  build: {
    chunkSizeWarningLimit: 1000,
  },
  server: {
    // Alinhar com APP_URL em 127.0.0.1 evita cookies invisíveis ao JS quando o API não é localhost.
    host: '127.0.0.1',
    port: 5173,
    strictPort: true,
  },
})
