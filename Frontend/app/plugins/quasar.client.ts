import { Quasar, Notify, Dialog, Loading } from 'quasar'
import {
  QLayout,
  QHeader,
  QFooter,
  QPageContainer,
  QToolbar,
  QToolbarTitle,
  QAvatar,
  QBtn,
  QIcon,
  QCard,
  QCardSection,
  QForm,
  QInput,
  QSelect,
  QToggle
} from 'quasar'

export default defineNuxtPlugin((nuxtApp) => {
  // S'assurer que Quasar ne se charge que côté client
  if (typeof window !== 'undefined') {
    nuxtApp.vueApp.use(Quasar, {
      plugins: {
        Notify,
        Dialog,
        Loading
      },
      components: {
        QLayout,
        QHeader,
        QFooter,
        QPageContainer,
        QToolbar,
        QToolbarTitle,
        QAvatar,
        QBtn,
        QIcon,
        QCard,
        QCardSection,
        QForm,
        QInput,
        QSelect,
        QToggle
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
