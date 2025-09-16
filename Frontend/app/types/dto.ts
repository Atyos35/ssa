
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


export interface CreateMissionDto {
  name: string
  description: string
  objectives: string
  danger: string
  status: string
  startDate: string
  endDate?: string | null
  countryId: number
  agentIds: number[]
}

export interface CreateMissionResponseDto {
  id: number
  name: string
  description: string
  objectives: string
  danger: string
  status: string
  startDate: string
  endDate?: string | null
  countryId: number
  agentIds: number[]
}


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


export interface ApiErrorDto {
  message: string
  errors?: Record<string, string[]>
  status: number
}



export interface CountrySelectOptionDto {
  label: string
  value: number
}



export interface ApiResponseDto<T> {
  data?: T
  error?: ApiErrorDto
  success: boolean
  message?: string
}

export interface UserDto {
  id: number // Changé de string (UUID) à number
  firstName: string
  lastName: string
  email: string
  roles: string[]
  emailVerified: boolean
}

export interface AgentDto {
  id: number // Changé de string (UUID) à number
  codeName: string
  firstName: string
  lastName: string
  email: string
  yearsOfExperience: number
  status: string
  enrolementDate: string
  infiltratedCountry?: CreateCountryResponseDto
  mentor?: AgentDto
}