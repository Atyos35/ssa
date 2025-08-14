// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  
  // Configuration TypeScript
  typescript: {
    strict: true,
    typeCheck: false  // Désactivé temporairement pour éviter les conflits
  },

  // Configuration Vite
  vite: {
    build: {
      target: 'esnext'
    },
    css: {
      preprocessorOptions: {
        sass: {
          quietDeps: true
        }
      }
    }
  },

  // Configuration Quasar avec nuxt-quasar-ui
  modules: [
    'nuxt-quasar-ui'
  ],

  // Configuration CSS pour Quasar
  css: [
    '@quasar/extras/material-icons/material-icons.css',
    '@quasar/extras/roboto-font/roboto-font.css',
    'quasar/dist/quasar.css',
    '~/assets/css/index.css'
  ]
})
