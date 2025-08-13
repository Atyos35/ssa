// Service d'authentification
import { apiService } from './api.service'
import type { RegisterRequest, RegisterResponse, ApiResponse } from '../types/api'

export interface LoginRequest {
  email: string
  password: string
}

export interface LoginResponse {
  token: string
  refresh_token: string
}

class AuthService {
  // Inscription d'un nouvel utilisateur
  async register(data: RegisterRequest): Promise<ApiResponse<RegisterResponse>> {
    const requestData = {
      ...data,
      roles: ['ROLE_USER'] // Rôle par défaut
    }
    
    return apiService.post<RegisterResponse>('/register', requestData)
  }

  // Connexion d'un utilisateur
  async login(data: LoginRequest): Promise<ApiResponse<LoginResponse>> {
    const response = await apiService.post<LoginResponse>('/login', data)
    
    // Si la connexion réussit, stocker le token
    if (response.success && response.data) {
      // Stocker le token ET le refresh_token
      localStorage.setItem('auth_token', response.data.token)
      localStorage.setItem('refresh_token', response.data.refresh_token)
    }
    
    return response
  }

  // Déconnexion
  logout(): void {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('refresh_token')
    // Rediriger vers la page de connexion ou d'accueil
  }

  // Vérifier si l'utilisateur est connecté
  isAuthenticated(): boolean {
    return !!this.getToken()
  }

  // Récupérer le token actuel
  getToken(): string | null {
    // Récupérer directement depuis localStorage pour éviter les incohérences
    const token = localStorage.getItem('auth_token')
    return token
  }

  // Rafraîchir le token
  async refreshToken(): Promise<ApiResponse<{ token: string }>> {
    try {
      // Récupérer le refresh_token stocké
      const refreshToken = localStorage.getItem('refresh_token')
      
      if (!refreshToken) {
        return {
          success: false,
          error: {
            message: 'Refresh token non disponible',
            status: 400,
          }
        }
      }
      
      // Utiliser fetch directement pour éviter le problème d'URL avec apiService
      const response = await fetch('http://localhost:8000/api/token/refresh', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
          refresh_token: refreshToken
        }),
      })
      
      const data = await response.json()
      
      if (!response.ok) {
        return {
          success: false,
          error: {
            message: data.message || data.error || 'Échec du rafraîchissement du token',
            status: response.status,
          }
        }
      }
      
      return {
        success: true,
        data: { token: data.token }
      }
    } catch (error) {
      return {
        success: false,
        error: {
          message: error instanceof Error ? error.message : 'Erreur réseau',
          status: 0,
        }
      }
    }
  }
}

// Instance singleton
export const authService = new AuthService()
