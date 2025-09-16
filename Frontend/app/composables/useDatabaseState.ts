import { ref, computed, onMounted } from 'vue'
import { databaseStateService } from '~/services/database-state.service'

export const useDatabaseState = () => {
  const isLoading = ref(true)
  const hasCountries = ref(false)
  const hasAgents = ref(false)
  const hasMissions = ref(false)

  const availableFeatures = computed(() => ({
    canCreateCountry: true, // Toujours disponible
    canCreateAgent: hasCountries.value,
    canViewAgentInfo: hasAgents.value,
    canCreateMission: hasAgents.value,
    canKillAgent: hasAgents.value,
    canViewMissionList: hasMissions.value,
    canCloseMission: hasMissions.value
  }))

  const isComplete = computed(() => 
    hasCountries.value && hasAgents.value && hasMissions.value
  )

  const loadDatabaseState = async () => {
    try {
      isLoading.value = true
      const state = await databaseStateService.getDatabaseState()
      
      hasCountries.value = state.hasCountries
      hasAgents.value = state.hasAgents
      hasMissions.value = state.hasMissions
    } catch (error) {
    } finally {
      isLoading.value = false
    }
  }

  const refreshDatabaseState = async () => {
    databaseStateService.invalidateCache()
    await loadDatabaseState()
  }

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