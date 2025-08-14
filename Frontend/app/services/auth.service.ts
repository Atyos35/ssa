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
    localStorage.removeItem('auth_token')
    localStorage.removeItem('refresh_token')
  }

  // Vérifier si l'utilisateur est connecté
  isAuthenticated(): boolean {
    const token = this.getToken()
    return !!token
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
      
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      return {
        success: false,
        error: {
          message: 'Échec du rafraîchissement du token',
          status: 500
        }
      }
    }
  }
}

// Instance singleton
export const authService = new AuthService()
export default authService
