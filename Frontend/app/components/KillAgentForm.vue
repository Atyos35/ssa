<template>
  <div class="kill-agent-form">
    <q-form @submit="handleSubmit" class="q-gutter-md">
      
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

const emit = defineEmits<{
  success: []
  error: [string]
  cancel: []
}>()

const { showSuccess, showError } = useNotification()

const loading = ref(false)
const availableAgents = ref<Array<{ label: string; value: number }>>([])

const form = ref({
  agentId: null as number | null
})

const isFormValid = computed(() => {
  return form.value.agentId != null
})

const loadAgents = async () => {
  try {
    const response = await agentService.getAgents()
    if (response.success && response.data) {

      availableAgents.value = response.data
        .filter((agent: any) => agent.status !== 'Killed in Action')
        .map((agent: any) => {
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
    
    if (error.response?.status === 401) {
      showError('Session expirée. Veuillez vous reconnecter.')
      if (typeof window !== 'undefined') {
        window.location.href = '/login'
      }
    } else {
      showError('Erreur lors du chargement des agents')
    }
  }
}

const onAgentChange = () => {
}

const handleSubmit = async () => {
  if (!isFormValid.value) {
    return
  }

  loading.value = true

  try {
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

onMounted(() => {
  loadAgents()
})
</script>