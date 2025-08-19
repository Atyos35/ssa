// Service pour la gestion des messages
import apiService from './api.service'
import type { ApiResponseDto } from '~/types/dto'

export class MessageService {
  /**
   * Récupérer tous les messages
   */
  async getMessages(): Promise<ApiResponseDto<any[]>> {
    try {
      const response = await apiService.get('/api/messages')
      return {
        success: true,
        data: response.data,
        message: 'Messages récupérés avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.message || 'Erreur lors de la récupération des messages',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Créer un nouveau message
   */
  async createMessage(messageData: {
    title: string
    body: string
    recipient: string
    by?: string
  }): Promise<ApiResponseDto<any>> {
    try {
      const response = await apiService.post('/api/messages', messageData)
      return {
        success: true,
        data: response.data,
        message: 'Message créé avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.message || 'Erreur lors de la création du message',
          status: error.response?.status || 500
        }
      }
    }
  }
}

export const messageService = new MessageService()
