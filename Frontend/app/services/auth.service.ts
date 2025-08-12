// Service d'authentification
import { apiService } from './api.service'
import type { RegisterRequest, RegisterResponse, ApiResponse } from '../types/api'

export interface LoginRequest {
  email: string
  password: string
}

export interface LoginResponse {
  token: string
  user: {
    id: string
    email: string
    firstName: string
    lastName: string
    roles: string[]
  }
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
      apiService.setAuthToken(response.data.token)
    }
    
    return response
  }

  // Déconnexion
  logout(): void {
    localStorage.removeItem('auth_token')
    // Rediriger vers la page de connexion ou d'accueil
  }

  // Vérifier si l'utilisateur est connecté
  isAuthenticated(): boolean {
    return !!apiService.getAuthToken()
  }

  // Récupérer le token actuel
  getToken(): string | null {
    return apiService.getAuthToken()
  }
}

// Instance singleton
export const authService = new AuthService()
