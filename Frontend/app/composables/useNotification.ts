import { Notify } from 'quasar'

export interface NotificationOptions {
  type?: 'success' | 'error' | 'warning' | 'info'
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

export const useNotification = () => {
  const showSuccess = (message: string, options?: Partial<NotificationOptions>) => {
    Notify.create({
      type: 'positive',
      message,
      icon: 'check_circle',
      color: 'positive',
      textColor: 'white',
      position: 'top-right',
      timeout: 3000,
      actions: [
        {
          icon: 'close',
          color: 'white',
          handler: () => {}
        }
      ],
      ...options
    })
  }

  const showError = (message: string, options?: Partial<NotificationOptions>) => {
    Notify.create({
      type: 'negative',
      message,
      icon: 'error',
      color: 'negative',
      textColor: 'white',
      position: 'top-right',
      timeout: 5000,
      actions: [
        {
          icon: 'close',
          color: 'white',
          handler: () => {}
        }
      ],
      ...options
    })
  }

  const showWarning = (message: string, options?: Partial<NotificationOptions>) => {
    Notify.create({
      type: 'warning',
      message,
      icon: 'warning',
      color: 'warning',
      textColor: 'white',
      position: 'top-right',
      timeout: 4000,
      actions: [
        {
          icon: 'close',
          color: 'white',
          handler: () => {}
        }
      ],
      ...options
    })
  }

  const showInfo = (message: string, options?: Partial<NotificationOptions>) => {
    Notify.create({
      type: 'info',
      message,
      icon: 'info',
      color: 'info',
      textColor: 'white',
      position: 'top-right',
      timeout: 3000,
      actions: [
        {
          icon: 'close',
          color: 'white',
          handler: () => {}
        }
      ],
      ...options
    })
  }

  const showNotification = (options: NotificationOptions) => {
    Notify.create({
      position: 'top-right',
      timeout: 3000,
      actions: [
        {
          icon: 'close',
          color: 'white',
          handler: () => {}
        }
      ],
      ...options
    })
  }

  const showApiResult = (result: { success: boolean; data?: any; error?: { message: string } }) => {
    if (result.success) {
      showSuccess('Opération réussie !')
    } else {
      showError(result.error?.message || 'Une erreur est survenue')
    }
  }

  return {
    showSuccess,
    showError,
    showWarning,
    showInfo,
    showNotification,
    showApiResult
  }
}