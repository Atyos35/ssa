// Types pour les API

export interface RegisterRequest {
  firstName: string
  lastName: string
  email: string
  password: string
  roles: string[]
}

export interface RegisterResponse {
  id: string
  firstName: string
  lastName: string
  email: string
  roles: string[]
  createdAt: string
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
  status: number
}

export interface ApiResponse<T> {
  data?: T
  error?: ApiError
  success: boolean
}
