<template>
  <div class="kill-agent-form">
    <q-form @submit="handleSubmit" class="q-gutter-md">
      <!-- Sélection de l'agent -->
      <q-select
        v-model="form.agentId"
        :options="availableAgents"
        label="Agent à tuer *"
        :rules="[val => !!val || 'L\'agent est requis']"
        outlined
        dense
        emit-value
        map-options
        @update:model-value="onAgentChange"
      />



      <!-- Boutons d'action -->
      <div class="form-actions">
        <q-btn
          type="submit"
          color="negative"
          :loading="loading"
          :disable="!isFormValid"
          label="Tuer l'agent"
          class="submit-btn"
        />
        <q-btn
          type="button"
          color="grey"
          label="Annuler"
          @click="$emit('cancel')"
          class="cancel-btn"
        />
      </div>
    </q-form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useNotification } from '~/composables/useNotification'
import { agentService } from '~/services/agent.service'

// Props et émissions
const emit = defineEmits<{
  success: []
  error: [string]
  cancel: []
}>()

// Composables
const { showSuccess, showError } = useNotification()

// État du formulaire
const loading = ref(false)
const availableAgents = ref<Array<{ label: string; value: number }>>([])

// Formulaire
const form = ref({
  agentId: null as number | null
})

// Validation du formulaire
const isFormValid = computed(() => {
  return form.value.agentId != null
})

// Charger les agents disponibles
const loadAgents = async () => {
  try {
    const response = await agentService.getAgents()
    if (response.success && response.data) {
      // Debug: afficher la structure des données
      console.log('Structure des agents reçus:', response.data)
      
      // Filtrer les agents qui ne sont pas déjà "Killed in Action"
      availableAgents.value = response.data
        .filter((agent: any) => agent.status !== 'Killed in Action')
        .map((agent: any) => {
          // Construire le label en gérant les propriétés manquantes
          const firstName = agent.firstName || ''
          const lastName = agent.lastName || ''
          const fullName = firstName && lastName ? ` (${firstName} ${lastName})` : ''
          
          return {
            label: `${agent.codeName}${fullName}`,
            value: agent.id
          }
        })
    }
  } catch (error: any) {
    console.error('Erreur lors du chargement des agents:', error)
    
    // Vérifier si c'est une erreur d'authentification
    if (error.response?.status === 401) {
      showError('Session expirée. Veuillez vous reconnecter.')
      // Rediriger vers la page de connexion
      if (typeof window !== 'undefined') {
        window.location.href = '/login'
      }
    } else {
      showError('Erreur lors du chargement des agents')
    }
  }
}

// Gestion du changement d'agent
const onAgentChange = () => {
  // Ici on pourrait ajouter de la logique si nécessaire
}

// Soumission du formulaire
const handleSubmit = async () => {
  if (!isFormValid.value) {
    return
  }

  loading.value = true

  try {
    // Mettre à jour le statut de l'agent
    const response = await agentService.updateAgentStatus(form.value.agentId!, 'Killed in Action')
    
    if (response.success) {
      const selectedAgent = availableAgents.value.find(agent => agent.value === form.value.agentId)
      const agentName = selectedAgent ? selectedAgent.label : 'Agent'
      showSuccess(`L'agent "${agentName}" a été marqué comme "Killed in Action"`)
      emit('success')
    } else {
      showError(response.error?.message || 'Erreur lors de la mise à jour du statut de l\'agent')
      emit('error', response.error?.message || 'Erreur lors de la mise à jour du statut de l\'agent')
    }
  } catch (error: any) {
    console.error('Erreur lors de la mise à jour du statut de l\'agent:', error)
    
    let errorMessage = 'Erreur lors de la mise à jour du statut de l\'agent'
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    }
    
    showError(errorMessage)
    emit('error', errorMessage)
  } finally {
    loading.value = false
  }
}

// Chargement initial
onMounted(() => {
  loadAgents()
})
</script>