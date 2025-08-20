<template>
  <div class="agent-info-form">
    <div class="q-pa-md">
      <div class="text-h6 q-mb-md">Informations de l'agent</div>
      
      <!-- Sélection de l'agent -->
      <div class="q-mb-lg">
        <q-select
          v-model="selectedAgent"
          :options="availableAgents"
          option-label="label"
          label="Sélectionner un agent"
          placeholder="Choisissez un agent par son code name"
          clearable
          @update:model-value="loadAgentInfo"
        />
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="text-center q-pa-lg">
        <q-spinner-dots size="50px" color="primary" />
        <div class="q-mt-sm">Chargement des informations...</div>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="text-center q-pa-lg">
        <q-icon name="error" size="50px" color="negative" />
        <div class="q-mt-sm text-negative">{{ error }}</div>
        <q-btn 
          label="Réessayer" 
          color="primary" 
          class="q-mt-md" 
          @click="loadAgentInfo"
        />
      </div>

      <!-- Informations de l'agent -->
      <div v-else-if="agentInfo" class="agent-details">
        <!-- Informations générales -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">
              <q-icon name="person" size="24px" class="q-mr-sm" />
              {{ agentInfo.codeName }}
            </div>
            
            <div class="row q-gutter-md">
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-6">Nom complet</div>
                <div class="text-body1">{{ agentInfo.firstName }} {{ agentInfo.lastName }}</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-6">Email</div>
                <div class="text-body1">{{ agentInfo.email }}</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-6">Années d'expérience</div>
                <div class="text-body1">{{ agentInfo.yearsOfExperience }} an(s)</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-6">Statut</div>
                <q-chip 
                  :color="getStatusColor(agentInfo.status)" 
                  text-color="white" 
                  size="sm"
                >
                  {{ getStatusLabel(agentInfo.status) }}
                </q-chip>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-6">Date d'enrôlement</div>
                <div class="text-body1">{{ formatDate(agentInfo.enrolementDate) }}</div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-caption text-grey-6">Pays infiltré</div>
                <div v-if="agentInfo.infiltratedCountry" class="text-body1">
                  <q-chip 
                    icon="location_on" 
                    size="sm" 
                    color="blue-3" 
                    text-color="blue-9"
                  >
                    {{ agentInfo.infiltratedCountry.name }}
                    <q-chip 
                      :color="getDangerColor(agentInfo.infiltratedCountry.dangerLevel)" 
                      text-color="white" 
                      size="xs"
                      class="q-ml-xs"
                    >
                      {{ getDangerLabel(agentInfo.infiltratedCountry.dangerLevel) }}
                    </q-chip>
                  </q-chip>
                </div>
                <div v-else class="text-body1 text-grey-6">Aucun pays infiltré</div>
              </div>
            </div>
          </q-card-section>
        </q-card>

        <!-- Missions -->
        <q-card class="q-mb-md">
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">
              <q-icon name="assignment" size="24px" class="q-mr-sm" />
              Missions ({{ agentInfo.missions?.length || 0 }})
            </div>
            
            <div v-if="agentInfo.missions && agentInfo.missions.length > 0">
              <q-list bordered separator>
                <q-item 
                  v-for="mission in agentInfo.missions" 
                  :key="mission.id"
                  class="mission-item"
                >
                  <q-item-section>
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
                    <q-item-label caption class="q-mt-xs">
                      <q-icon name="schedule" size="16px" class="q-mr-xs" />
                      Du {{ formatDate(mission.startDate) }}
                      <span v-if="mission.endDate" class="q-mx-sm">au {{ formatDate(mission.endDate) }}</span>
                    </q-item-label>
                    <q-item-label caption class="q-mt-xs">
                      <q-icon name="location_on" size="16px" class="q-mr-xs" />
                      {{ mission.country?.name || 'Pays inconnu' }}
                      <span class="q-mx-sm">•</span>
                      <q-icon name="warning" size="16px" class="q-mr-xs" />
                      {{ getDangerLabel(mission.danger) }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
            <div v-else class="text-center q-pa-lg text-grey-6">
              Aucune mission assignée
            </div>
          </q-card-section>
        </q-card>

        <!-- Messages -->
        <q-card>
          <q-card-section>
            <div class="text-h6 text-primary q-mb-md">
              <q-icon name="message" size="24px" class="q-mr-sm" />
              Messages ({{ agentInfo.messages?.length || 0 }})
            </div>
            
            <div v-if="agentInfo.messages && agentInfo.messages.length > 0">
              <q-list bordered separator>
                <q-item 
                  v-for="message in agentInfo.messages" 
                  :key="message.id"
                  class="message-item"
                >
                  <q-item-section>
                    <q-item-label class="text-weight-medium">
                      {{ message.title }}
                    </q-item-label>
                    <q-item-label caption class="q-mt-xs">
                      {{ message.body }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>
            <div v-else class="text-center q-pa-lg text-grey-6">
              Aucun message
            </div>
          </q-card-section>
        </q-card>
      </div>

      <!-- État initial -->
      <div v-else class="text-center q-pa-lg">
        <q-icon name="person_search" size="50px" color="grey-5" />
        <div class="q-mt-sm text-grey-6">Sélectionnez un agent pour voir ses informations</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { agentService } from '~/services'

// État du composant
const loading = ref(false)
const error = ref<string | null>(null)
const selectedAgent = ref<{ label: string; value: string | number } | null>(null)
const availableAgents = ref<Array<{ label: string; value: string | number }>>([])
const agentInfo = ref<any>(null)

// Charger la liste des agents
const loadAgents = async () => {
  try {
    const response = await agentService.getAgents()
    
    if (response.success && response.data) {
      availableAgents.value = response.data.map((agent: any) => ({
        label: agent.codeName,
        value: agent.id
      }))
    } else {
      error.value = response.error?.message || 'Erreur lors du chargement des agents'
    }
  } catch (err: any) {
    console.error('Erreur lors du chargement des agents:', err)
    
    if (err.response?.status === 401) {
      error.value = 'Session expirée. Veuillez vous reconnecter.'
      if (typeof window !== 'undefined') {
        window.location.href = '/login'
      }
    } else {
      error.value = 'Erreur lors du chargement des agents'
    }
  }
}

// Charger les informations d'un agent
const loadAgentInfo = async () => {
  if (!selectedAgent.value) {
    agentInfo.value = null
    return
  }

  loading.value = true
  error.value = null

  try {
    const response = await agentService.getAgent(selectedAgent.value.value)
    
    if (response.success && response.data) {
      agentInfo.value = response.data
    } else {
      error.value = response.error?.message || 'Erreur lors du chargement des informations de l\'agent'
    }
  } catch (err: any) {
    console.error('Erreur lors du chargement des informations de l\'agent:', err)
    
    if (err.response?.status === 401) {
      error.value = 'Session expirée. Veuillez vous reconnecter.'
      if (typeof window !== 'undefined') {
        window.location.href = '/login'
      }
    } else {
      error.value = 'Erreur lors du chargement des informations de l\'agent'
    }
  } finally {
    loading.value = false
  }
}

// Utilitaires pour l'affichage
const getStatusColor = (status: string): string => {
  switch (status) {
    case 'Available': return 'positive'
    case 'OnMission': return 'orange'
    case 'Killed': return 'negative'
    case 'Retired': return 'grey'
    default: return 'grey'
  }
}

const getStatusLabel = (status: string): string => {
  switch (status) {
    case 'Available': return 'Disponible'
    case 'OnMission': return 'En mission'
    case 'Killed': return 'Tué'
    case 'Retired': return 'Retraité'
    default: return status
  }
}

const getDangerColor = (danger: string): string => {
  switch (danger) {
    case 'Low': return 'positive'
    case 'Medium': return 'warning'
    case 'High': return 'orange'
    case 'Critical': return 'negative'
    default: return 'grey'
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
  loadAgents()
})
</script>