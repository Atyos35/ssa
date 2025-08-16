interface ApiRequestOptions {
  method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE'
  url: string
  data?: any
  headers?: Record<string, string>
}

interface ApiResponse<T = any> {
  data: T
  status: number
  statusText: string
}

class ApiService {
  private baseUrl = 'http://127.0.0.1:8000'

  private getAuthHeaders(): Record<string, string> {
    const headers: Record<string, string> = {
      'Content-Type': 'application/ld+json',
    }

    // Récupérer le token directement depuis localStorage
    if (typeof window !== 'undefined') {
      const token = localStorage.getItem('auth_token')
      if (token) {
        headers['Authorization'] = `Bearer ${token}`
      }
    }

    return headers
  }

  async request<T = any>(options: ApiRequestOptions): Promise<ApiResponse<T>> {
    try {
      const headers = this.getAuthHeaders()
      
      const config: RequestInit = {
        method: options.method,
        headers: {
          ...headers,
          ...options.headers,
        },
      }

      if (options.data && ['POST', 'PATCH'].includes(options.method)) {
        config.body = JSON.stringify(options.data)
      }

      const fullUrl = options.url.startsWith('http') ? options.url : `${this.baseUrl}${options.url}`
      
      const response = await fetch(fullUrl, config)
      
      // Si on a une erreur 401 (Unauthorized), essayer de rafraîchir le token
      if (response.status === 401) {
        try {
          const refreshResult = await this.refreshToken()
          if (refreshResult.success && refreshResult.data) {
            // Token rafraîchi, réessayer la requête originale
            const newHeaders = this.getAuthHeaders()
            const newConfig: RequestInit = {
              method: options.method,
              headers: {
                ...newHeaders,
                ...options.headers,
              },
            }

            if (options.data && ['POST', 'PATCH'].includes(options.method)) {
              newConfig.body = JSON.stringify(options.data)
            }

            const retryResponse = await fetch(fullUrl, newConfig)
            
            if (!retryResponse.ok) {
              throw new Error(`HTTP error! status: ${retryResponse.status}`)
            }

            let data: T
            const contentType = retryResponse.headers.get('content-type')
            
            if (contentType && contentType.includes('application/json')) {
              data = await retryResponse.json()
            } else {
              data = await retryResponse.text() as T
            }

            return {
              data,
              status: retryResponse.status,
              statusText: retryResponse.statusText,
            }
          }
        } catch (refreshError) {
          // Si le refresh échoue, rediriger vers la page de connexion
          console.error('Token refresh failed:', refreshError)
          this.handleAuthFailure()
          throw new Error('Authentication failed')
        }
      }
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      let data: T
      const contentType = response.headers.get('content-type')
      
      if (contentType && contentType.includes('application/json')) {
        data = await response.json()
      } else {
        data = await response.text() as T
      }

      return {
        data,
        status: response.status,
        statusText: response.statusText,
      }
    } catch (error) {
      console.error('API request failed:', error)
      throw error
    }
  }

  // Méthodes utilitaires pour les requêtes courantes
  async get<T = any>(url: string, headers?: Record<string, string>): Promise<ApiResponse<T>> {
    return this.request<T>({ method: 'GET', url, headers })
  }

  async post<T = any>(url: string, data: any, headers?: Record<string, string>): Promise<ApiResponse<T>> {
    return this.request<T>({ method: 'POST', url, data, headers })
  }

  async patch<T = any>(url: string, data: any, headers?: Record<string, string>): Promise<ApiResponse<T>> {
    return this.request<T>({ method: 'PATCH', url, data, headers })
  }

  async put<T = any>(url: string, data: any, headers?: Record<string, string>): Promise<ApiResponse<T>> {
    return this.request<T>({ method: 'PUT', url, data, headers })
  }

  async delete<T = any>(url: string, headers?: Record<string, string>): Promise<ApiResponse<T>> {
    return this.request<T>({ method: 'DELETE', url, headers })
  }

  // Rafraîchir le token d'authentification
  private async refreshToken(): Promise<{ success: boolean; data?: { token: string } }> {
    try {
      const refreshToken = localStorage.getItem('refresh_token')
      
      if (!refreshToken) {
        return { success: false }
      }
      
      const response = await fetch(`${this.baseUrl}/api/token/refresh`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          refresh_token: refreshToken
        })
      })
      
      if (response.ok) {
        const data = await response.json()
        // Stocker le nouveau token
        localStorage.setItem('auth_token', data.token)
        return { success: true, data }
      }
      
      return { success: false }
    } catch (error) {
      console.error('Refresh token failed:', error)
      return { success: false }
    }
  }

  // Gérer l'échec de l'authentification
  private handleAuthFailure(): void {
    // Nettoyer les tokens
    localStorage.removeItem('auth_token')
    localStorage.removeItem('refresh_token')
    
    // Rediriger vers la page de connexion si on est dans le navigateur
    if (typeof window !== 'undefined') {
      window.location.href = '/login'
    }
  }
}

export default new ApiService()
