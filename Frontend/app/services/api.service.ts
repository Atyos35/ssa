// Service API principal
import type { ApiResponse, ApiError } from '../types/api'

class ApiService {
  private baseURL: string = 'http://localhost:8000/api'

  // Configuration par défaut pour fetch
  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    try {
      const url = `${this.baseURL}${endpoint}`
      
      const config: RequestInit = {
        headers: {
          'Content-Type': 'application/json',
          ...this.getAuthHeaders(),
          ...options.headers,
        },
        ...options,
      }

      const response = await fetch(url, config)
      const data = await response.json()

      // Si le token a expiré (401), essayer de le rafraîchir automatiquement
      if (response.status === 401 && this.getAuthToken() && endpoint !== '/token/refresh') {
        try {
          const refreshResult = await this.refreshToken()
          if (refreshResult.success && refreshResult.data) {
            // Mettre à jour le token et retenter la requête originale
            localStorage.setItem('auth_token', refreshResult.data.token)
            
            // Retenter la requête originale avec le nouveau token
            const retryConfig = {
              ...config,
              headers: {
                ...config.headers,
                ...this.getAuthHeaders(),
              }
            }
            
            const retryResponse = await fetch(url, retryConfig)
            const retryData = await retryResponse.json()
            
            if (!retryResponse.ok) {
              const error: ApiError = {
                message: retryData.message || retryData.error || 'Une erreur est survenue',
                errors: retryData.errors,
                status: retryResponse.status,
              }
              
              return {
                success: false,
                error,
              }
            }
            
            return {
              success: true,
              data: retryData,
            }
          }
        } catch (refreshError) {
          // Supprimer le token invalide
          localStorage.removeItem('auth_token')
        }
      }

      if (!response.ok) {
        const error: ApiError = {
          message: data.message || data.error || 'Une erreur est survenue',
          errors: data.errors,
          status: response.status,
        }
        
        return {
          success: false,
          error,
        }
      }

      return {
        success: true,
        data,
      }
    } catch (error) {
      const apiError: ApiError = {
        message: error instanceof Error ? error.message : 'Erreur réseau',
        status: 0,
      }
      
      return {
        success: false,
        error: apiError,
      }
    }
  }

  // Méthodes HTTP
  async get<T>(endpoint: string): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: 'GET' })
  }

  async post<T>(endpoint: string, data?: any): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  async put<T>(endpoint: string, data?: any): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    })
  }

  // Méthode pour ajouter un token d'authentification
  setAuthToken(token: string) {
    // Stocker le token pour les futures requêtes
    localStorage.setItem('auth_token', token)
  }

  // Méthode pour récupérer le token d'authentification
  getAuthToken(): string | null {
    const token = localStorage.getItem('auth_token')
    return token
  }

  // Méthode pour ajouter automatiquement le token aux headers
  private getAuthHeaders(): Record<string, string> {
    const token = this.getAuthToken()
    return token ? { Authorization: `Bearer ${token}` } : {}
  }

  // Méthode pour rafraîchir le token
  private async refreshToken(): Promise<ApiResponse<{ token: string }>> {
    try {
      // Récupérer le refresh_token stocké
      const refreshToken = localStorage.getItem('refresh_token')
      
      if (!refreshToken) {
        throw new Error('Refresh token non disponible')
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
        const error: ApiError = {
          message: data.message || data.error || 'Échec du rafraîchissement du token',
          status: response.status,
        }
        
        return {
          success: false,
          error,
        }
      }
      
      return {
        success: true,
        data,
      }
    } catch (error) {
      const apiError: ApiError = {
        message: error instanceof Error ? error.message : 'Erreur réseau',
        status: 0,
      }
      
      return {
        success: false,
        error: apiError,
      }
    }
  }
}

// Instance singleton
export const apiService = new ApiService()
