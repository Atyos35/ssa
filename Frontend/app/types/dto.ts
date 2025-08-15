// ========================================
// REGISTRATION DTOs
// ========================================

export interface CreateUserDto {
  firstName: string
  lastName: string
  email: string
  password: string
  roles: string[]
}

export interface CreateUserResponseDto {
  id: string
  firstName: string
  lastName: string
  email: string
  roles: string[]
  createdAt: string
}

// ========================================
// COUNTRY DTOs
// ========================================

export interface CreateCountryDto {
  name: string
}

export interface CreateCountryResponseDto {
  id: number
  name: string
  danger?: string
  numberOfAgents?: number
  missions: any[]
  agents: any[]
}

// ========================================
// AGENT DTOs
// ========================================

export interface CreateAgentDto {
  codeName: string
  firstName: string
  lastName: string
  email: string
  password: string
  yearsOfExperience: number
  infiltratedCountryId: number
  status?: string
  enrolementDate?: string
  mentorId?: number
}

export interface CreateAgentResponseDto {
  message: string
  agent?: {
    id: number
    codeName: string
    firstName: string
    lastName: string
    email: string
    yearsOfExperience: number
    status: string
    enrolementDate: string
    infiltratedCountryId: number
    mentorId?: number
  }
}

// ========================================
// COMMON DTOs
// ========================================

export interface BaseResponseDto {
  success: boolean
  message: string
}

export interface ValidationErrorDto {
  field: string
  message: string
}

export interface ApiErrorDto {
  message: string
  errors?: ValidationErrorDto[]
  status: number
}

// ========================================
// FORM DTOs
// ========================================

export interface AgentFormDto {
  codeName: string
  firstName: string
  lastName: string
  email: string
  password: string
  yearsOfExperience: number
  infiltratedCountryId: number
}

export interface CountryFormDto {
  name: string
}

export interface UserFormDto {
  firstName: string
  lastName: string
  email: string
  password: string
}

// ========================================
// SELECT OPTION DTOs
// ========================================

export interface SelectOptionDto {
  label: string
  value: number | string
}

export interface CountrySelectOptionDto {
  label: string
  value: number
}

// ========================================
// VALIDATION DTOs
// ========================================

export interface ValidationResultDto<T> {
  success: boolean
  data?: T
  errors?: Record<string, string>
}

// ========================================
// API RESPONSE DTOs
// ========================================

export interface ApiResponseDto<T> {
  data?: T
  error?: ApiErrorDto
  success: boolean
  message?: string
}
