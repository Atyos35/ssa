<template>
  <div class="mission-closure-form">
    <q-form @submit="handleSubmit" class="q-gutter-md">
      <!-- Sélection de la mission -->
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

      <!-- Statut de la mission -->
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

      <!-- Summary de la mission -->
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

      <!-- Boutons d'action -->
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
const availableMissions = ref<Array<{ label: string; value: string }>>([])

// Formulaire
const form = ref<MissionClosureFormRawData>({
  missionId: null,
  status: '',
  summary: ''
})

// Statuts de mission disponibles
const missionStatuses = [
  { label: 'Succès', value: 'Success' },
  { label: 'Échec', value: 'Failure' }
]

// Validation du formulaire
const isFormValid = computed(() => {
  return form.value.missionId != null &&
         form.value.status &&
         form.value.summary
})

// Charger les missions disponibles
const loadMissions = async () => {
  try {
    const response = await missionService.getMissions()
    if (response.success && response.data) {
      // Filtrer les missions qui ne sont pas encore clôturées
      availableMissions.value = response.data
        .filter((mission: any) => mission.status === 'InProgress')
        .map((mission: any) => ({
          label: `${mission.name} - ${mission.country?.name || 'Pays inconnu'}`,
          value: mission.id
        }))
    }
  } catch (error: any) {
    console.error('Erreur lors du chargement des missions:', error)
    
    // Vérifier si c'est une erreur d'authentification
    if (error.response?.status === 401) {
      showError('Session expirée. Veuillez vous reconnecter.')
      // Rediriger vers la page de connexion
      if (typeof window !== 'undefined') {
        window.location.href = '/login'
      }
    } else {
      showError('Erreur lors du chargement des missions')
    }
  }
}

// Gestion du changement de mission
const onMissionChange = () => {
  // Réinitialiser le summary quand on change de mission
  form.value.summary = ''
}

// Soumission du formulaire
const handleSubmit = async () => {
  if (!isFormValid.value) {
    return
  }

  loading.value = true

  try {
    // Validation avec le schéma Zod
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

    // Mettre à jour la mission avec le nouveau statut et summary
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

// Chargement initial
onMounted(() => {
  loadMissions()
})
</script>