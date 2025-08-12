// Service API principal
import type { ApiResponse, ApiError } from '../types/api'

class ApiService {
  private baseURL: string = 'http://127.0.0.1:8000/api'

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

      if (!response.ok) {
        console.log(data.error)
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
    return localStorage.getItem('auth_token')
  }

  // Méthode pour ajouter automatiquement le token aux headers
  private getAuthHeaders(): Record<string, string> {
    const token = this.getAuthToken()
    return token ? { Authorization: `Bearer ${token}` } : {}
  }
}

// Instance singleton
export const apiService = new ApiService()
