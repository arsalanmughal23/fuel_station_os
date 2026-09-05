// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },

  // Disable SSR for Tauri compatibility
  ssr: false,

  // Runtime config for API base URL
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8080/api/v1',
    },
  },

  typescript: {
    strict: true,
    typeCheck: false,
  },

  modules: [
    '@nuxtjs/tailwindcss',
  ],

  tailwindcss: {
    cssPath: '~/assets/css/main.css',
    configPath: 'tailwind.config.js',
  },

  nitro: {
    preset: 'static',
    output: {
      publicDir: 'dist',
    },
  },

  app: {
    head: {
      title: 'Fuel Station OS',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
      ],
    },
  },

  // Vite configuration for Tauri compatibility
  vite: {
    optimizeDeps: {
      include: ['vue', 'vue-router', '@nuxtjs/tailwindcss', '@tauri-apps/api', 'nuxt'],
    },
    // Tauri expects the dev server to be accessible from the Tauri app
    server: {
      port: 1420,
      strictPort: true,
      hmr: {
        protocol: 'ws',
        host: 'localhost',
        port: 1420,
      },
    },
    // Ensure Tauri can load the app correctly
    build: {
      target: 'esnext',
      minify: 'esbuild',
    },
  },

  compatibilityDate: '2024-08-27',
})