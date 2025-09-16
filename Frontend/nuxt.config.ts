export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  
  typescript: {
    strict: true,
    typeCheck: false
  },

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

  modules: [
    'nuxt-quasar-ui'
  ],

  quasar: {
    extras: {
      fontIcons: ['material-icons']
    },
    plugins: ['Notify']
  },

  css: [
    '@quasar/extras/material-icons/material-icons.css',
    '@quasar/extras/roboto-font/roboto-font.css',
    'quasar/dist/quasar.css',
    '~/assets/css/index.css',
    '~/assets/css/form.css',
    '~/assets/css/auth.css'
  ]
})
