<template>
  <div class="mission-closure-form">
    <q-form @submit="handleSubmit" class="q-gutter-md">
      
      <q-select
        v-model="form.missionId"
        :options="availableMissions"
        label="Mission à clôturer *"
        :rules="[val => !!val || 'La mission est requise']"
        outlined
        dense
        emit-value
        map-options
        @update:model-value="onMissionChange"
      />

      <q-select
        v-model="form.status"
        :options="missionStatuses"
        label="Statut de la mission *"
        :rules="[val => !!val || 'Le statut est requis']"
        outlined
        dense
        emit-value
        map-options
      />

      <q-input
        v-model="form.summary"
        label="Résumé de la mission *"
        type="textarea"
        :rules="[
          val => !!val || 'Le résumé est requis',
          val => val.length <= 1000 || 'Le résumé ne peut pas dépasser 1000 caractères'
        ]"
        outlined
        dense
        rows="4"
      />

      <div class="form-actions">
        <q-btn
          type="submit"
          color="primary"
          :loading="loading"
          :disable="!isFormValid"
          label="Clôturer la mission"
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
import { missionService } from '~/services'
import { missionClosureSchema, type MissionClosureFormData, type MissionClosureFormRawData } from '~/schemas/mission-closure.schema'

const emit = defineEmits<{
  success: []
  error: [string]
  cancel: []
}>()

const { showSuccess, showError } = useNotification()

const loading = ref(false)
const availableMissions = ref<Array<{ label: string; value: string }>>([])

const form = ref<MissionClosureFormRawData>({
  missionId: null,
  status: '',
  summary: ''
})

const missionStatuses = [
  { label: 'Succès', value: 'Success' },
  { label: 'Échec', value: 'Failure' }
]

const isFormValid = computed(() => {
  return form.value.missionId != null &&
         form.value.status &&
         form.value.summary
})

const loadMissions = async () => {
  try {
    const response = await missionService.getMissions()
    if (response.success && response.data) {
      availableMissions.value = response.data
        .filter((mission: any) => mission.status === 'InProgress')
        .map((mission: any) => ({
          label: `${mission.name} - ${mission.country?.name || 'Pays inconnu'}`,
          value: mission.id
        }))
    }
  } catch (error: any) {
    console.error('Erreur lors du chargement des missions:', error)
    
    if (error.response?.status === 401) {
      showError('Session expirée. Veuillez vous reconnecter.')
      if (typeof window !== 'undefined') {
        window.location.href = '/login'
      }
    } else {
      showError('Erreur lors du chargement des missions')
    }
  }
}

const onMissionChange = () => {
  form.value.summary = ''
}

const handleSubmit = async () => {
  if (!isFormValid.value) {
    return
  }

  loading.value = true

  try {
    const missionData: MissionClosureFormData = {
      missionId: String(form.value.missionId!),
      status: form.value.status as 'Success' | 'Failure',
      summary: form.value.summary
    }

    const validationResult = missionClosureSchema.safeParse(missionData)
    if (!validationResult.success) {
      const errorMessage = validationResult.error.issues.map(e => e.message).join(', ')
      showError(`Erreur de validation : ${errorMessage}`)
      return
    }

    const response = await missionService.updateMission(Number(missionData.missionId), {
      status: missionData.status,
      missionResultSummary: missionData.summary
    })
    
    if (response.success) {
      showSuccess('Mission clôturée avec succès')
      emit('success')
    } else {
      showError(response.error?.message || 'Erreur lors de la clôture de la mission')
      emit('error', response.error?.message || 'Erreur lors de la clôture de la mission')
    }
  } catch (error: any) {
    console.error('Erreur lors de la clôture de la mission:', error)
    
    let errorMessage = 'Erreur lors de la clôture de la mission'
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
  loadMissions()
})
</script>