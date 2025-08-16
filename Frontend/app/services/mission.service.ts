import apiService from './api.service'
import type { ApiResponseDto } from '~/types/dto'

export class MissionService {
  /**
   * Créer une nouvelle mission
   */
  async createMission(missionData: {
    name: string
    description: string
    objectives: string
    danger: string
    status: string
    startDate: string
    endDate?: string | null
    countryId: number
    agentIds: number[]
  }): Promise<ApiResponseDto<any>> {
    try {
      const response = await apiService.post('/api/missions', missionData)
      return {
        success: true,
        data: response.data,
        message: 'Mission créée avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.message || 'Erreur lors de la création de la mission',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Récupérer toutes les missions
   */
  async getMissions(): Promise<ApiResponseDto<any[]>> {
    try {
      const response = await apiService.get('/api/missions')
      return {
        success: true,
        data: response.data,
        message: 'Missions récupérées avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.message || 'Erreur lors de la récupération des missions',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Récupérer une mission par son ID
   */
  async getMission(id: number): Promise<ApiResponseDto<any>> {
    try {
      const response = await apiService.get(`/api/missions/${id}`)
      return {
        success: true,
        data: response.data,
        message: 'Mission récupérée avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.message || 'Erreur lors de la récupération de la mission',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Mettre à jour une mission
   */
  async updateMission(id: number, missionData: Partial<{
    name: string
    description: string
    objectives: string
    danger: string
    status: string
    startDate: string
    endDate: string | null
    countryId: number
    agentIds: number[]
  }>): Promise<ApiResponseDto<any>> {
    try {
      const response = await apiService.put(`/api/missions/${id}`, missionData)
      return {
        success: true,
        data: response.data,
        message: 'Mission mise à jour avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.message || 'Erreur lors de la mise à jour de la mission',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Supprimer une mission
   */
  async deleteMission(id: number): Promise<ApiResponseDto<void>> {
    try {
      await apiService.delete(`/api/missions/${id}`)
      return {
        success: true,
        message: 'Mission supprimée avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.message || 'Erreur lors de la suppression de la mission',
          status: error.response?.status || 500
        }
      }
    }
  }
}

export const missionService = new MissionService()
