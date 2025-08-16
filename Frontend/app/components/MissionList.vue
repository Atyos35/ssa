<template>
  <div class="mission-list">
    <div class="q-pa-md">
      <div class="text-h6 q-mb-md">Liste des missions et leurs résultats</div>
      
      <!-- Loading state -->
      <div v-if="loading" class="text-center q-pa-lg">
        <q-spinner-dots size="50px" color="primary" />
        <div class="q-mt-sm">Chargement des missions...</div>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="text-center q-pa-lg">
        <q-icon name="error" size="50px" color="negative" />
        <div class="q-mt-sm text-negative">{{ error }}</div>
        <q-btn 
          label="Réessayer" 
          color="primary" 
          class="q-mt-md" 
          @click="loadMissions"
        />
      </div>

             <!-- Missions list -->
       <div v-else-if="missions.length > 0">
         <q-list bordered separator class="missions-list">
          <q-item 
            v-for="mission in missions" 
            :key="mission.id"
            clickable 
            v-ripple
            class="mission-item"
          >
            <q-item-section>
              <!-- Mission name and status -->
              <q-item-label class="text-weight-medium">
                {{ mission.name }}
                <q-chip 
                  :color="getStatusColor(mission.status)" 
                  text-color="white" 
                  size="sm"
                  class="q-ml-sm"
                >
                  {{ getStatusLabel(mission.status) }}
                </q-chip>
              </q-item-label>

              <!-- Mission details -->
              <q-item-label caption class="q-mt-xs">
                <q-icon name="location_on" size="16px" class="q-mr-xs" />
                {{ mission.country?.name || 'Pays inconnu' }}
                <span class="q-mx-sm">•</span>
                <q-icon name="warning" size="16px" class="q-mr-xs" />
                {{ getDangerLabel(mission.danger) }}
              </q-item-label>

              <!-- Mission dates -->
              <q-item-label caption class="q-mt-xs">
                <q-icon name="schedule" size="16px" class="q-mr-xs" />
                Du {{ formatDate(mission.startDate) }}
                <span v-if="mission.endDate" class="q-mx-sm">au {{ formatDate(mission.endDate) }}</span>
              </q-item-label>

              <!-- Mission result if exists -->
              <div v-if="mission.missionResult" class="q-mt-sm">
                <q-separator class="q-my-sm" />
                <div class="text-caption text-weight-medium text-primary">
                  <q-icon name="assignment_turned_in" size="16px" class="q-mr-xs" />
                  Résultat de la mission
                </div>
                <div class="text-caption q-mt-xs">
                  <strong>Statut final :</strong> 
                  <q-chip 
                    :color="getStatusColor(mission.missionResult.status)" 
                    text-color="white" 
                    size="xs"
                    class="q-ml-xs"
                  >
                    {{ getStatusLabel(mission.missionResult.status) }}
                  </q-chip>
                </div>
                <div v-if="mission.missionResult.summary" class="text-caption q-mt-xs">
                  <strong>Résumé :</strong> {{ mission.missionResult.summary }}
                </div>
              </div>

              <!-- Agents count -->
              <div class="q-mt-sm">
                <q-chip 
                  icon="people" 
                  size="sm" 
                  color="grey-3" 
                  text-color="grey-8"
                >
                  {{ mission.agents?.length || 0 }} agent(s)
                </q-chip>
              </div>
            </q-item-section>

            
          </q-item>
        </q-list>
      </div>

      <!-- Empty state -->
      <div v-else class="text-center q-pa-lg">
        <q-icon name="assignment" size="50px" color="grey-5" />
        <div class="q-mt-sm text-grey-6">Aucune mission trouvée</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { missionService } from '~/services'



// État du composant
const loading = ref(false)
const error = ref<string | null>(null)
const missions = ref<any[]>([])

// Charger les missions
const loadMissions = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await missionService.getMissionsWithResults()
    
    if (response.success && response.data) {
      missions.value = response.data
    } else {
      error.value = response.error?.message || 'Erreur lors du chargement des missions'
    }
  } catch (err: any) {
    console.error('Erreur lors du chargement des missions:', err)
    
    if (err.response?.status === 401) {
      error.value = 'Session expirée. Veuillez vous reconnecter.'
      if (typeof window !== 'undefined') {
        window.location.href = '/login'
      }
    } else {
      error.value = 'Erreur lors du chargement des missions'
    }
  } finally {
    loading.value = false
  }
}

// Utilitaires pour l'affichage
const getStatusColor = (status: string): string => {
  switch (status) {
    case 'InProgress': return 'orange'
    case 'Success': return 'positive'
    case 'Failure': return 'negative'
    case 'Cancelled': return 'grey'
    default: return 'grey'
  }
}

const getStatusLabel = (status: string): string => {
  switch (status) {
    case 'InProgress': return 'En cours'
    case 'Success': return 'Succès'
    case 'Failure': return 'Échec'
    case 'Cancelled': return 'Annulée'
    default: return status
  }
}

const getDangerLabel = (danger: string): string => {
  switch (danger) {
    case 'Low': return 'Faible'
    case 'Medium': return 'Moyen'
    case 'High': return 'Élevé'
    case 'Critical': return 'Critique'
    default: return danger
  }
}

const formatDate = (dateString: string): string => {
  if (!dateString) return 'Date inconnue'
  
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch {
    return dateString
  }
}



// Chargement initial
onMounted(() => {
  loadMissions()
})
</script>

<style scoped>
.mission-list {
  width: 100%;
  max-width: 100%;
}

.missions-list {
  max-height: 70vh;
  overflow-y: auto;
}

.mission-item {
  transition: all 0.2s ease;
  padding: 16px;
}

.mission-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.mission-item .q-item__section {
  min-width: 0;
}

/* Optimisation pour les écrans larges */
@media (min-width: 900px) {
  .mission-item {
    padding: 20px;
  }
  
  .mission-item .q-item__section {
    padding-right: 20px;
  }
}

/* Responsive design */
@media (max-width: 768px) {
  .mission-list {
    padding: 0 10px;
  }
  
  .mission-item {
    padding: 12px;
  }
}
</style>
