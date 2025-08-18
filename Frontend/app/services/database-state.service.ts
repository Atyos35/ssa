import { apiService } from './index'
import type { ApiResponseDto } from '~/types/api'

export class DatabaseStateService {
  private static instance: DatabaseStateService
  private cache: Map<string, { data: any; timestamp: number }> = new Map()
  private readonly CACHE_DURATION = 30000 // 30 secondes

  static getInstance(): DatabaseStateService {
    if (!DatabaseStateService.instance) {
      DatabaseStateService.instance = new DatabaseStateService()
    }
    return DatabaseStateService.instance
  }

  private isCacheValid(key: string): boolean {
    const cached = this.cache.get(key)
    if (!cached) return false
    return Date.now() - cached.timestamp < this.CACHE_DURATION
  }

  private setCache(key: string, data: any): void {
    this.cache.set(key, { data, timestamp: Date.now() })
  }

  private getCache(key: string): any | null {
    const cached = this.cache.get(key)
    return cached ? cached.data : null
  }

  async hasCountries(): Promise<boolean> {
    const cacheKey = 'hasCountries'
    if (this.isCacheValid(cacheKey)) {
      return this.getCache(cacheKey)
    }

    try {
      const response = await apiService.get('/api/countries')
      
      // L'API retourne une chaîne JSON, il faut la parser
      let parsedData
      try {
        parsedData = typeof response.data === 'string' ? JSON.parse(response.data) : response.data
      } catch (parseError) {
        return false
      }
      
      // Vérifier différentes structures possibles
      const hasCountries = (
        (parsedData && parsedData.totalItems > 0) ||
        (parsedData && parsedData.member && parsedData.member.length > 0) ||
        (parsedData && Array.isArray(parsedData) && parsedData.length > 0) ||
        (parsedData && parsedData['hydra:member'] && parsedData['hydra:member'].length > 0)
      )
      
      this.setCache(cacheKey, hasCountries)
      return hasCountries
    } catch (error) {
      return false
    }
  }

  async hasAgents(): Promise<boolean> {
    const cacheKey = 'hasAgents'
    if (this.isCacheValid(cacheKey)) {
      return this.getCache(cacheKey)
    }

    try {
      const response = await apiService.get('/api/agents')
      
      // L'API retourne une chaîne JSON, il faut la parser
      let parsedData
      try {
        parsedData = typeof response.data === 'string' ? JSON.parse(response.data) : response.data
            } catch (parseError) {
        return false
      }
      
      // Vérifier différentes structures possibles
      const hasAgents = (
        (parsedData && parsedData.totalItems > 0) ||
        (parsedData && parsedData.member && parsedData.member.length > 0) ||
        (parsedData && Array.isArray(parsedData) && parsedData.length > 0) ||
        (parsedData && parsedData['hydra:member'] && parsedData['hydra:member'].length > 0)
      )
      
      this.setCache(cacheKey, hasAgents)
      return hasAgents
    } catch (error) {
      return false
    }
  }

  async hasMissions(): Promise<boolean> {
    const cacheKey = 'hasMissions'
    if (this.isCacheValid(cacheKey)) {
      return this.getCache(cacheKey)
    }

    try {
      const response = await apiService.get('/api/missions')
      
      // L'API retourne une chaîne JSON, il faut la parser
      let parsedData
      try {
        parsedData = typeof response.data === 'string' ? JSON.parse(response.data) : response.data
      } catch (parseError) {
        return false
      }
      
      // Vérifier différentes structures possibles
      const hasMissions = (
        (parsedData && parsedData.totalItems > 0) ||
        (parsedData && parsedData.member && parsedData.member.length > 0) ||
        (parsedData && Array.isArray(parsedData) && parsedData.length > 0) ||
        (parsedData && parsedData['hydra:member'] && parsedData['hydra:member'].length > 0)
      )
      
      this.setCache(cacheKey, hasMissions)
      return hasMissions
    } catch (error) {
      return false
    }
  }

  // Invalider le cache après une opération qui modifie la base
  invalidateCache(): void {
    this.cache.clear()
  }

  // Vérifier l'état complet de la base
  async getDatabaseState(): Promise<{
    hasCountries: boolean
    hasAgents: boolean
    hasMissions: boolean
  }> {
    const [hasCountries, hasAgents, hasMissions] = await Promise.all([
      this.hasCountries(),
      this.hasAgents(),
      this.hasMissions()
    ])

    return { hasCountries, hasAgents, hasMissions }
  }
}

export const databaseStateService = DatabaseStateService.getInstance()
