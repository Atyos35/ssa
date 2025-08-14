<template>
  <!-- Ce composant n'a pas de template car il utilise Quasar Notify -->
</template>

<script setup lang="ts">
import { useQuasar } from 'quasar'

// Props du composant
interface Props {
  type: 'success' | 'error' | 'warning' | 'info'
  message: string
  caption?: string
  timeout?: number
  position?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right' | 'top' | 'bottom' | 'left' | 'right' | 'center'
  icon?: string
  color?: string
  textColor?: string
  actions?: Array<{
    label?: string
    icon?: string
    color?: string
    handler?: () => void
  }>
}

const props = withDefaults(defineProps<Props>(), {
  timeout: 3000,
  position: 'top-right',
  color: undefined,
  textColor: undefined,
  icon: undefined,
  actions: () => []
})

// Utiliser Quasar
const $q = useQuasar()

// Fonction pour afficher la notification
const showNotification = () => {
  const notificationConfig = {
    type: props.type,
    message: props.message,
    caption: props.caption,
    timeout: props.timeout,
    position: props.position,
    icon: props.icon || getDefaultIcon(),
    color: props.color || getDefaultColor(),
    textColor: props.textColor || getDefaultTextColor(),
    actions: props.actions.length > 0 ? props.actions : [
      {
        icon: 'close',
        color: 'white',
        handler: () => {}
      }
    ]
  }

  $q.notify(notificationConfig)
}

// Icônes par défaut selon le type
const getDefaultIcon = (): string => {
  switch (props.type) {
    case 'success':
      return 'check_circle'
    case 'error':
      return 'error'
    case 'warning':
      return 'warning'
    case 'info':
      return 'info'
    default:
      return 'notifications'
  }
}

// Couleurs par défaut selon le type
const getDefaultColor = (): string => {
  switch (props.type) {
    case 'success':
      return 'positive'
    case 'error':
      return 'negative'
    case 'warning':
      return 'warning'
    case 'info':
      return 'info'
    default:
      return 'primary'
  }
}

// Couleur de texte par défaut
const getDefaultTextColor = (): string => {
  return 'white'
}

// Exposer la fonction pour utilisation externe
defineExpose({
  show: showNotification
})

// Afficher automatiquement la notification si le composant est monté
onMounted(() => {
  showNotification()
})
</script>
