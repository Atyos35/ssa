// Service pour la gestion des pays
import apiService from './api.service'
import type { 
  CreateCountryDto, 
  CreateCountryResponseDto,
  ApiResponseDto 
} from '~/types'

export class CountryService {
  /**
   * Créer un nouveau pays
   */
  static async createCountry(countryData: CreateCountryDto): Promise<ApiResponseDto<CreateCountryResponseDto>> {
    try {
      const response = await apiService.post('/api/countries', countryData)
      return {
        success: true,
        data: response.data,
        message: 'Pays créé avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.error || 'Erreur lors de la création du pays',
          status: error.response?.status || 500,
          errors: error.response?.data?.errors
        }
      }
    }
  }

  /**
   * Récupérer un pays par son ID
   */
  static async getCountryById(id: number): Promise<ApiResponseDto<CreateCountryResponseDto>> {
    try {
      const response = await apiService.get(`/api/countries/${id}`)
      return {
        success: true,
        data: response.data,
        message: 'Pays récupéré avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.error || 'Erreur lors de la récupération du pays',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Récupérer la liste des pays (pour les listes déroulantes)
   */
  static async getCountries(): Promise<ApiResponseDto<CreateCountryResponseDto[]>> {
    try {
      // Utiliser un endpoint simple pour éviter les références circulaires
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
      
      return {
        success: true,
        data: parsedData.member || [],
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
   * Récupérer la liste des pays pour les listes déroulantes (sans relations)
   */
  static async getCountriesForSelect(): Promise<ApiResponseDto<Array<{ label: string; value: number }>>> {
    try {
      console.log('Tentative de récupération des pays via API...')
      
      // Utiliser l'endpoint standard maintenant que la référence circulaire est corrigée
      const response = await apiService.get('/api/countries')
      console.log('Réponse API reçue:', response)
      
      let parsedData
      try {
        parsedData = typeof response.data === 'string' ? JSON.parse(response.data) : response.data
        console.log('Données parsées:', parsedData)
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
      
      // Extraire seulement les données essentielles
      const countries = parsedData.member || []
      console.log('Pays extraits:', countries)
      
      const formattedCountries = countries.map((country: any) => {
        if (!country.id || !country.name) {
          console.warn('Pays avec données incomplètes:', country)
          return null
        }
        
        return {
          label: country.name,
          value: country.id
        }
      }).filter(Boolean)
      
      console.log('Pays formatés:', formattedCountries)
      
      return {
        success: true,
        data: formattedCountries,
        message: 'Pays récupérés avec succès'
      }
    } catch (error: any) {
      console.error('Erreur complète lors de la récupération des pays:', error)
      console.error('Détails de l\'erreur:', error.response?.data)
      
      return {
        success: false,
        error: {
          message: error.response?.data?.detail || error.response?.data?.error || 'Erreur lors de la récupération des pays',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Mettre à jour un pays
   */
  static async updateCountry(id: number, countryData: Partial<CreateCountryDto>): Promise<ApiResponseDto<CreateCountryResponseDto>> {
    try {
      const response = await apiService.put(`/api/countries/${id}`, countryData)
      return {
        success: true,
        data: response.data,
        message: 'Pays mis à jour avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.error || 'Erreur lors de la mise à jour du pays',
          status: error.response?.status || 500
        }
      }
    }
  }

  /**
   * Supprimer un pays
   */
  static async deleteCountry(id: number): Promise<ApiResponseDto<void>> {
    try {
      await apiService.delete(`/api/countries/${id}`)
      return {
        success: true,
        message: 'Pays supprimé avec succès'
      }
    } catch (error: any) {
      return {
        success: false,
        error: {
          message: error.response?.data?.error || 'Erreur lors de la suppression du pays',
          status: error.response?.status || 500
        }
      }
    }
  }
}
