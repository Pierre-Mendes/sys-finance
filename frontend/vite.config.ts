import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'
import { subresourceIntegrity } from './build/subresourceIntegrity'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
    subresourceIntegrity(),
  ],
  resolve: {
    alias: [
      { find: '@', replacement: path.resolve(__dirname, './src') },
      // Build do SweetAlert2 SEM o CSS embutido: a versão padrão injeta uma tag <style> em tempo de execução,
      // o que exigiria 'unsafe-inline' na CSP. O CSS vem como arquivo em main.ts.
      { find: /^sweetalert2$/, replacement: 'sweetalert2/dist/sweetalert2.esm.js' },
    ],
  },
  server: {
    // No Docker (docker-compose.yml) o alvo é o container "backend"; fora dele, a API local na 8081.
    watch: process.env.VITE_USE_POLLING === 'true' ? { usePolling: true, interval: 300 } : undefined,
    proxy: {
      '/api': {
        target: process.env.VITE_PROXY_TARGET || 'http://localhost:8081',
        changeOrigin: true,
        secure: false,
      }
    }
  }
})
