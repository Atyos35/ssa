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
  private readonly REFRESH_INTERVAL_MS = 25 * 60 * 1000
  async register(data: RegisterRequest): Promise<ApiResult<RegisterResponse>> {
    try {
      const requestData = {
        ...data,
        roles: ['ROLE_USER']
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

  async login(data: LoginRequest): Promise<ApiResult<LoginResponse>> {
    try {
      const response = await apiService.post<LoginResponse>('/api/login', data)
      
      if (response.data) {
        localStorage.setItem('auth_token', response.data.token)
        localStorage.setItem('refresh_token', response.data.refresh_token)
        
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

  logout(): void {
    this.stopAutoRefresh()
    
    localStorage.removeItem('auth_token')
    localStorage.removeItem('refresh_token')
  }

  isAuthenticated(): boolean {
    const token = this.getToken()
    const isAuth = !!token
    
    if (isAuth && !this.refreshInterval) {
      this.startAutoRefresh()
    }
    
    return isAuth
  }

  getToken(): string | null {
    const token = localStorage.getItem('auth_token')
    return token
  }

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
      
      if (response.data) {
        localStorage.setItem('auth_token', response.data.token)

      }
      
      return {
        success: true,
        data: response.data
      }
    } catch (error) {
      console.error('Échec du refresh automatique:', error)
      
      this.stopAutoRefresh()
      this.logout()
      
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

  private startAutoRefresh(): void {
    this.stopAutoRefresh()
    
    this.refreshInterval = setInterval(async () => {
      
      await this.refreshToken()
    }, this.REFRESH_INTERVAL_MS)

  }

  private stopAutoRefresh(): void {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval)
      this.refreshInterval = null
      
    }
  }

  async getCurrentUser(): Promise<ApiResult<any>> {
    try {
      const token = this.getToken()
      
      if (!token) {
        return {
          success: false,
          error: {
            message: 'Aucun token d\'authentification',
            status: 401
          }
        }
      }

      const response = await apiService.get('/api/me')
      return {
        success: true,
        data: response.data
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.message || 'Erreur lors de la récupération des informations utilisateur',
          status: error.response?.status || 500
        }
      }
    }
  }
}

export const authService = new AuthService()
export default authService