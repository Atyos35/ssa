import { ref, computed, onMounted } from 'vue'
import { databaseStateService } from '~/services/database-state.service'

export const useDatabaseState = () => {
  const isLoading = ref(true)
  const hasCountries = ref(false)
  const hasAgents = ref(false)
  const hasMissions = ref(false)

  // Fonctionnalités disponibles selon l'état de la base
  const availableFeatures = computed(() => ({
    canCreateCountry: true, // Toujours disponible
    canCreateAgent: hasCountries.value,
    canViewAgentInfo: hasAgents.value,
    canCreateMission: hasAgents.value,
    canKillAgent: hasAgents.value,
    canViewMissionList: hasMissions.value,
    canCloseMission: hasMissions.value
  }))

  // Vérifier si le parcours est complet
  const isComplete = computed(() => 
    hasCountries.value && hasAgents.value && hasMissions.value
  )

  // Charger l'état de la base
  const loadDatabaseState = async () => {
    try {
      isLoading.value = true
      const state = await databaseStateService.getDatabaseState()
      
      hasCountries.value = state.hasCountries
      hasAgents.value = state.hasAgents
      hasMissions.value = state.hasMissions
    } catch (error) {
      // Gérer l'erreur silencieusement
    } finally {
      isLoading.value = false
    }
  }

  // Rafraîchir l'état (après une opération qui modifie la base)
  const refreshDatabaseState = async () => {
    databaseStateService.invalidateCache()
    await loadDatabaseState()
  }

  // Charger l'état au montage du composant
  onMounted(() => {
    loadDatabaseState()
  })

  return {
    isLoading,
    hasCountries,
    hasAgents,
    hasMissions,
    availableFeatures,
    isComplete,
    loadDatabaseState,
    refreshDatabaseState
  }
}
