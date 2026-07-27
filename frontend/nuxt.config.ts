// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  devtools: { enabled: true },
  
  nitro: {
    preset: 'node-server'
  },
  
  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost:8001/api'
    }
  },
  
  typescript: {
    typeCheck: false, // Disable type checking during dev to avoid vue-tsc issues
    strict: true
  },
  
  compatibilityDate: '2024-07-21'
})
