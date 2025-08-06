declare module 'quasar' {
  import { App } from 'vue'
  
  export interface QuasarPluginOptions {
    plugins?: Record<string, any>
    config?: {
      brand?: {
        primary?: string
        secondary?: string
        accent?: string
        dark?: string
        darkPage?: string
        positive?: string
        negative?: string
        info?: string
        warning?: string
      }
      notify?: {
        position?: string
        timeout?: number
        textColor?: string
      }
    }
  }

  export const Quasar: {
    install(app: App, options?: QuasarPluginOptions): void
  }

  export const Notify: any
  export const Dialog: any
  export const Loading: any
} 