// Service pour la gestion des agents
import apiService from './api.service'
import type { ApiResponseDto } from '~/types/dto'

export class AgentService {
  /**
   * Récupérer tous les agents
   */
  async getAgents(): Promise<ApiResponseDto<any[]>> {
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
          message: error.response?.data?.message || 'Erreur lors de la récupération des agents',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Récupérer un agent par son ID
   */
  async getAgent(id: number): Promise<ApiResponseDto<any>> {
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
          message: error.response?.data?.message || 'Erreur lors de la récupération de l\'agent',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Créer un nouvel agent
   */
  async createAgent(agentData: {
    codeName: string
    firstName: string
    lastName: string
    email: string
    password: string
    yearsOfExperience: number
    infiltratedCountryId: number
    mentorId?: number
  }): Promise<ApiResponseDto<any>> {
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
          message: error.response?.data?.message || 'Erreur lors de la création de l\'agent',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Mettre à jour un agent
   */
  async updateAgent(id: number, agentData: Partial<{
    codeName: string
    firstName: string
    lastName: string
    email: string
    password: string
    yearsOfExperience: number
    infiltratedCountryId: number
    mentorId: number
    status?: string
  }>): Promise<ApiResponseDto<any>> {
    try {
      const response = await apiService.patch(`/api/agents/${id}`, agentData)
      return {
        success: true,
        data: response.data,
        message: 'Agent mis à jour avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.message || 'Erreur lors de la mise à jour de l\'agent',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Mettre à jour le statut d'un agent
   */
  async updateAgentStatus(id: number, status: string): Promise<ApiResponseDto<any>> {
    try {
      const response = await apiService.patch(`/api/agents/${id}/status`, { status })
      return {
        success: true,
        data: response.data,
        message: 'Statut de l\'agent mis à jour avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.message || 'Erreur lors de la mise à jour du statut de l\'agent',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Supprimer un agent
   */
  async deleteAgent(id: number): Promise<ApiResponseDto<void>> {
    try {
      await apiService.delete(`/api/agents/${id}`)
      return {
        success: true,
        message: 'Agent supprimé avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.message || 'Erreur lors de la suppression de l\'agent',
          status: error.response?.status || 500
        }
      }
    }
  }

  // Méthodes statiques pour la compatibilité avec AgentForm
  static async getCountriesForSelect(): Promise<ApiResponseDto<any[]>> {
    try {
      const response = await apiService.get('/api/countries')
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
      
      // Formater les pays pour le composant q-select
      const countriesData = parsedData.member || []
      const formattedCountries = countriesData.map((country: any) => ({
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
          message: error.response?.data?.message || 'Erreur lors de la récupération des pays',
          status: error.response?.status || 500
        }
      }
    }
  }

  static async createAgent(agentData: {
    codeName: string
    firstName: string
    lastName: string
    email: string
    password: string
    yearsOfExperience: number
    infiltratedCountryId: number
    mentorId?: number
  }): Promise<ApiResponseDto<any>> {
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
          message: error.response?.data?.message || 'Erreur lors de la création de l\'agent',
          status: error.response?.status || 500
        }
      }
    }
  }
}

export const agentService = new AgentService()
