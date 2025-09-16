<template>
  <div class="mission-form">
    <q-form @submit="handleSubmit" class="q-gutter-md">
      
      <q-input
        v-model="form.name"
        label="Nom de la mission *"
        :rules="[val => !!val || 'Le nom est requis', val => val.length >= 3 || 'Le nom doit contenir au moins 3 caractères']"
        outlined
        dense
      />

      <q-input
        v-model="form.description"
        label="Description *"
        type="textarea"
        :rules="[val => !!val || 'La description est requise', val => val.length <= 500 || 'La description ne peut pas dépasser 500 caractères']"
        outlined
        dense
        rows="3"
      />

      <q-input
        v-model="form.objectives"
        label="Objectifs *"
        type="textarea"
        :rules="[val => !!val || 'Les objectifs sont requis', val => val.length <= 500 || 'Les objectifs ne peuvent pas dépasser 500 caractères']"
        outlined
        dense
        rows="3"
      />

      <q-select
        v-model="form.danger"
        :options="dangerLevels"
        label="Niveau de danger *"
        :rules="[val => !!val || 'Le niveau de danger est requis']"
        outlined
        dense
        emit-value
        map-options
      />

       <q-input
         v-model="form.startDate"
         label="Date de début *"
         type="date"
         :rules="[val => !!val || 'La date de début est requise']"
         outlined
         dense
         readonly
         disable
       />

       <q-input
         v-model="form.endDate"
         label="Date de fin"
         type="date"
         :rules="[val => !val || (form.startDate && val > form.startDate) || 'La date de fin doit être postérieure à la date de début']"
         outlined
         dense
       />

      <q-select
        v-model="form.countryId"
        :options="countries"
        label="Pays *"
        :rules="[val => !!val || 'Le pays est requis']"
        outlined
        dense
        emit-value
        map-options
        @update:model-value="onCountryChange"
      />

       <q-select
         v-model="form.agentIds"
         :options="availableAgents"
         label="Agents"
         multiple
         outlined
         dense
         emit-value
         map-options
         use-chips
         @update:model-value="validateAgentCountry"
       />

      <div v-if="agentCountryError" class="text-negative text-caption q-mt-xs">
        {{ agentCountryError }}
      </div>

      <div class="form-actions">
        <q-btn
          type="submit"
          color="primary"
          :loading="loading"
          :disable="!isFormValid"
          label="Créer la mission"
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
import { missionService, countryService, agentService } from '~/services'
import { missionSchema, type MissionFormData } from '~/schemas/mission.schema'

const emit = defineEmits<{
  success: []
  error: [string]
  cancel: []
}>()

const { showSuccess, showError } = useNotification()

const loading = ref(false)
const countries = ref<Array<{ label: string; value: number }>>([])
const availableAgents = ref<Array<{ label: string; value: number; countryId: number }>>([])
const agentCountryError = ref('')

const form = ref({
  name: '',
  description: '',
  objectives: '',
  danger: '' as 'Low' | 'Medium' | 'High' | 'Critical' | '',
  startDate: new Date().toISOString().split('T')[0],
  endDate: '',
  countryId: null as number | null,
  agentIds: [] as number[]
})

const dangerLevels = [
  { label: 'Faible', value: 'Low' },
  { label: 'Moyen', value: 'Medium' },
  { label: 'Élevé', value: 'High' },
  { label: 'Critique', value: 'Critical' }
]

const isFormValid = computed(() => {
  return form.value.name &&
         form.value.description &&
         form.value.objectives &&
         form.value.danger &&
         form.value.startDate &&
         form.value.countryId &&
         !agentCountryError.value
})

const loadCountries = async () => {
  try {
    const response = await countryService.getCountriesForSelect()
    if (response.success && response.data) {
      countries.value = response.data
    }
  } catch (error) {
    console.error('Erreur lors du chargement des pays:', error)
    showError('Erreur lors du chargement des pays')
  }
}

const loadAgents = async () => {
  try {
    const response = await agentService.getAgents()
    if (response.success && response.data) {
      availableAgents.value = response.data
        .filter((agent: any) => agent.infiltratedCountry != null)
        .map((agent: any) => ({
          label: agent.codeName,
          value: agent.id,
          countryId: agent.infiltratedCountry.id
        }))
    }
  } catch (error) {
    console.error('Erreur lors du chargement des agents:', error)
    showError('Erreur lors du chargement des agents')
  }
}

const onCountryChange = () => {
  form.value.agentIds = []
  agentCountryError.value = ''
}

const validateAgentCountry = () => {
  if (!form.value.countryId || form.value.agentIds.length === 0) {
    agentCountryError.value = ''
    return
  }

  const invalidAgents = form.value.agentIds.filter(agentId => {
    const agent = availableAgents.value.find(a => a.value === agentId)
    if (!agent) return false
    
    const agentCountryId = Number(agent.countryId)
    const selectedCountryId = Number(form.value.countryId)
    
    return agentCountryId !== selectedCountryId
  })

  if (invalidAgents.length > 0) {
    const invalidAgentNames = invalidAgents.map(agentId => {
      const agent = availableAgents.value.find(a => a.value === agentId)
      return agent ? agent.label : `Agent ${agentId}`
    }).join(', ')
    
    agentCountryError.value = `Les agents suivants ne sont pas infiltrés dans le pays sélectionné : ${invalidAgentNames}`
  } else {
    agentCountryError.value = ''
  }
}

const handleSubmit = async () => {
  if (!isFormValid.value) {
    return
  }

  loading.value = true

  try {
         const missionData: MissionFormData = {
       name: form.value.name,
       description: form.value.description,
       objectives: form.value.objectives,
       danger: form.value.danger as 'Low' | 'Medium' | 'High' | 'Critical',
       startDate: form.value.startDate!,
       endDate: form.value.endDate || undefined,
       countryId: form.value.countryId!,
       agentIds: form.value.agentIds
     }

    const validationResult = missionSchema.safeParse(missionData)
    if (!validationResult.success) {
      const errorMessage = validationResult.error.issues.map(e => e.message).join(', ')
      showError(`Erreur de validation : ${errorMessage}`)
      return
    }

    const response = await missionService.createMission({
      ...missionData,
      status: 'InProgress'
    })
    
    if (response.success) {
      showSuccess('Mission créée avec succès')
      emit('success')
    } else {
      showError(response.error?.message || 'Erreur lors de la création de la mission')
      emit('error', response.error?.message || 'Erreur lors de la création de la mission')
    }
  } catch (error: any) {
    console.error('Erreur lors de la création de la mission:', error)
    
    let errorMessage = 'Erreur lors de la création de la mission'
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
  loadCountries()
  loadAgents()
})
</script>