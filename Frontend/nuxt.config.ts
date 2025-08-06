// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  
  // Configuration TypeScript
  typescript: {
    strict: true,
    typeCheck: true
  },

  // Configuration CSS simplifiée
  css: [
    '@quasar/extras/material-icons/material-icons.css',
    '@quasar/extras/roboto-font/roboto-font.css'
  ],

  // Configuration Vite pour Quasar
  vite: {
    define: {
      'process.env.DEBUG': false,
    }
  },

  // Configuration des modules
  modules: [
    '@nuxtjs/tailwindcss'
  ],

  // Configuration de l'application
  app: {
    head: {
      title: 'SSA - Secret Service Application',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'description', content: 'Application de service secret avec Quasar et Nuxt' }
      ]
    }
  }
})
