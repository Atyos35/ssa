// Service d'authentification
import apiService from './api.service'

export interface LoginRequest {
  email: string
  password: string
}

export interface LoginResponse {
  token: string
  refresh_token: string
}

export interface RegisterRequest {
  email: string
  password: string
  firstName: string
  lastName: string
}

export interface RegisterResponse {
  message: string
}

interface ApiResult<T> {
  success: boolean
  data?: T
  error?: {
    message: string
    status?: number
  }
}

class AuthService {
  private refreshInterval: NodeJS.Timeout | null = null
  private readonly REFRESH_INTERVAL_MS = 25 * 60 * 1000 // 25 minutes
  // Inscription d'un nouvel utilisateur
  async register(data: RegisterRequest): Promise<ApiResult<RegisterResponse>> {
    try {
      const requestData = {
        ...data,
        roles: ['ROLE_USER'] // Rôle par défaut
      }
      
      const response = await apiService.post<RegisterResponse>('/api/register', requestData)
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: {
          message: error instanceof Error ? error.message : 'Erreur lors de l\'inscription',
          status: 500
        }
      }
    }
  }

  // Connexion d'un utilisateur
  async login(data: LoginRequest): Promise<ApiResult<LoginResponse>> {
    try {
      const response = await apiService.post<LoginResponse>('/api/login', data)
      
      // Si la connexion réussit, stocker le token
      if (response.data) {
        // Stocker le token ET le refresh_token
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('refresh_token', response.data.refresh_token)
        
        // Démarrer le refresh automatique
        this.startAutoRefresh()
      }
      
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: {
          message: error instanceof Error ? error.message : 'Identifiants incorrects',
          status: 401
        }
      }
    }
  }

  // Déconnexion
  logout(): void {
    // Arrêter le refresh automatique
    this.stopAutoRefresh()
    
    // Nettoyer le localStorage
    localStorage.removeItem('auth_token')
    localStorage.removeItem('refresh_token')
  }

  // Vérifier si l'utilisateur est connecté
  isAuthenticated(): boolean {
    const token = this.getToken()
    const isAuth = !!token
    
    // Si l'utilisateur est authentifié et qu'il n'y a pas d'intervalle de refresh, le redémarrer
    if (isAuth && !this.refreshInterval) {
      this.startAutoRefresh()
    }
    
    return isAuth
  }

  // Récupérer le token actuel
  getToken(): string | null {
    const token = localStorage.getItem('auth_token')
    return token
  }

  // Rafraîchir le token
  async refreshToken(): Promise<ApiResult<{ token: string }>> {
    try {
      const refreshToken = localStorage.getItem('refresh_token')
      
      if (!refreshToken) {
        return {
          success: false,
          error: {
            message: 'Refresh token non disponible',
            status: 400
          }
        }
      }
      
      const response = await apiService.post<{ token: string }>('/api/token/refresh', {
        refresh_token: refreshToken
      })
      
      // Si le refresh réussit, stocker le nouveau token
      if (response.data) {
        localStorage.setItem('auth_token', response.data.token)
        console.log('Token rafraîchi automatiquement')
      }
      
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      console.error('Échec du refresh automatique:', error)
      
      // Si le refresh échoue, arrêter l'intervalle et rediriger vers login
      this.stopAutoRefresh()
      this.logout()
      
      // Rediriger vers la page de connexion
      if (typeof window !== 'undefined') {
        window.location.href = '/login'
      }
      
      return {
        success: false,
        error: {
          message: 'Échec du rafraîchissement du token',
          status: 500
        }
      }
    }
  }

  // Démarrer le refresh automatique
  private startAutoRefresh(): void {
    // Arrêter l'intervalle existant s'il y en a un
    this.stopAutoRefresh()
    
    // Démarrer un nouvel intervalle
    this.refreshInterval = setInterval(async () => {
      console.log('Refresh automatique du token...')
      await this.refreshToken()
    }, this.REFRESH_INTERVAL_MS)
    
    console.log(`Refresh automatique démarré - intervalle: ${this.REFRESH_INTERVAL_MS / 1000 / 60} minutes`)
  }

  // Arrêter le refresh automatique
  private stopAutoRefresh(): void {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval)
      this.refreshInterval = null
      console.log('Refresh automatique arrêté')
    }
  }

  // Forcer un refresh manuel (utile pour les tests)
  async forceRefresh(): Promise<ApiResult<{ token: string }>> {
    console.log('Refresh manuel du token...')
    return await this.refreshToken()
  }

  // Vérifier l'état du refresh automatique
  isAutoRefreshActive(): boolean {
    return this.refreshInterval !== null
  }
}

// Instance singleton
export const authService = new AuthService()
export default authService
