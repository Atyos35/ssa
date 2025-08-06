import { Quasar, Notify, Dialog, Loading } from 'quasar'
import '@quasar/extras/material-icons/material-icons.css'
import 'quasar/dist/quasar.css'

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.use(Quasar, {
    plugins: {
      Notify,
      Dialog,
      Loading
    },
    config: {
      brand: {
        primary: '#1976D2',
        secondary: '#26A69A',
        accent: '#9C27B0',
        dark: '#1D1D1D',
        darkPage: '#121212',
        positive: '#21BA45',
        negative: '#C10015',
        info: '#31CCEC',
        warning: '#F2C037'
      },
      notify: {
        position: 'top-right',
        timeout: 2500,
        textColor: 'white'
      }
    }
  })
}) 