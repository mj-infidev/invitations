import { searchForWorkspaceRoot, defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      // Components shared by the storefront and the admin (repo-root /shared).
      '@shared': fileURLToPath(new URL('../shared', import.meta.url)),
    },
    // /shared has no node_modules of its own: its `import 'vue'` must resolve to
    // this app's copy, or the page would run two Vues and the component breaks.
    dedupe: ['vue'],
  },
  server: {
    // The dev server only serves files inside the app by default; allow /shared.
    fs: { allow: [searchForWorkspaceRoot(process.cwd()), fileURLToPath(new URL('../shared', import.meta.url))] },
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
})
