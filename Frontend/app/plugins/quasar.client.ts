import { Quasar, Notify, Dialog, Loading } from 'quasar'
import * as components from 'quasar'

export default defineNuxtPlugin((nuxtApp) => {
  // Vérifier si on est côté client
  if (typeof window !== 'undefined') {
    nuxtApp.vueApp.use(Quasar, {
      plugins: {
        Notify,
        Dialog,
        Loading
      },
      components: {
        QBtn: components.QBtn,
        QIcon: components.QIcon,
        QCard: components.QCard,
        QCardSection: components.QCardSection,
        QForm: components.QForm,
        QInput: components.QInput,
        QSelect: components.QSelect,
        QToggle: components.QToggle
      },
      config: {
        brand: {
          primary: '#1976d2',
          secondary: '#26a69a',
          accent: '#9c27b0',
          dark: '#1d1d1d'
        }
      }
    })
  }
})
