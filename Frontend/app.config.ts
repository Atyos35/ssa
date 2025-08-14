import { defineAppConfig } from 'nuxt/app'

export default defineAppConfig({
  // Configure Quasar's Vue plugin (with HMR support)
  nuxtQuasar: {
    brand: {
      primary: '#1976d2',
      secondary: '#26a69a',
      accent: '#9c27b0',
      dark: '#1d1d1d'
    }
  }
})
