// Service pour la gestion des agents
import apiService from './api.service'
import type { 
  CreateAgentDto, 
  CreateAgentResponseDto, 
  CountrySelectOptionDto,
  ApiResponseDto 
} from '~/types'

export class AgentService {
  /**
   * Créer un nouvel agent
   */
  static async createAgent(agentData: CreateAgentDto): Promise<ApiResponseDto<CreateAgentResponseDto>> {
    try {
      const response = await apiService.post('/api/agents', agentData)
      return {
        success: true,
        data: response.data,
        message: 'Agent créé avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.error || 'Erreur lors de la création de l\'agent',
          status: error.response?.status || 500,
          errors: error.response?.data?.errors
        }
      }
    }
  }

  /**
   * Récupérer la liste des pays pour la sélection
   */
  static async getCountriesForSelect(): Promise<ApiResponseDto<CountrySelectOptionDto[]>> {
    try {
      const response = await apiService.get('/api/countries')
      
      // Parser la réponse JSON si nécessaire
      let parsedData
      try {
        parsedData = typeof response.data === 'string' ? JSON.parse(response.data) : response.data
      } catch (e) {
        console.error('Erreur parsing JSON:', e)
        return {
          success: false,
          error: {
            message: 'Erreur lors du parsing des données',
            status: 500
          }
        }
      }
      
      // Formater les pays pour le select
      const countriesData = parsedData.member || []
      const formattedCountries: CountrySelectOptionDto[] = countriesData.map((country: any) => ({
        label: country.name,
        value: country.id
      }))
      
      return {
        success: true,
        data: formattedCountries,
        message: 'Pays récupérés avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.error || 'Erreur lors de la récupération des pays',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Récupérer un agent par son ID
   */
  static async getAgentById(id: number): Promise<ApiResponseDto<any>> {
    try {
      const response = await apiService.get(`/api/agents/${id}`)
      return {
        success: true,
        data: response.data,
        message: 'Agent récupéré avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.error || 'Erreur lors de la récupération de l\'agent',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Récupérer la liste des agents
   */
  static async getAgents(): Promise<ApiResponseDto<any[]>> {
    try {
      const response = await apiService.get('/api/agents')
      return {
        success: true,
        data: response.data,
        message: 'Agents récupérés avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.error || 'Erreur lors de la récupération des agents',
          status: error.response?.status || 500
        }
      }
    }
  }
}
