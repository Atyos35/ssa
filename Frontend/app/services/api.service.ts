interface ApiRequestOptions {
  method: 'GET' | 'POST' | 'PATCH'
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
}

export default new ApiService()
