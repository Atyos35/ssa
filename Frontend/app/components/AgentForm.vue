<template>
  <div class="agent-form">
    <form @submit.prevent="handleSubmit">
             <div class="form-group">
         <q-input
           v-model="formData.codeName"
           :error="!!errors.codeName"
           :error-message="errors.codeName"
           placeholder="Nom de code *"
           outlined
           dense
           @blur="validateField('codeName')"
         />
       </div>

       <div class="form-group">
         <q-input
           v-model="formData.firstName"
           :error="!!errors.firstName"
           :error-message="errors.firstName"
           placeholder="Prénom *"
           outlined
           dense
           @blur="validateField('firstName')"
         />
       </div>

       <div class="form-group">
         <q-input
           v-model="formData.lastName"
           :error="!!errors.lastName"
           :error-message="errors.lastName"
           placeholder="Nom *"
           outlined
           dense
           @blur="validateField('lastName')"
         />
       </div>

       <div class="form-group">
         <q-input
           v-model="formData.email"
           type="email"
           :error="!!errors.email"
           :error-message="errors.email"
           placeholder="Email *"
           outlined
           dense
           @blur="validateField('email')"
         />
       </div>

       <div class="form-group">
         <q-input
           v-model="formData.password"
           type="password"
           :error="!!errors.password"
           :error-message="errors.password"
           placeholder="Mot de passe *"
           outlined
           dense
           @blur="validateField('password')"
         />
       </div>

       <div class="form-group">
         <q-input
           v-model.number="formData.yearsOfExperience"
           type="number"
           :error="!!errors.yearsOfExperience"
           :error-message="errors.yearsOfExperience"
           label="Années d'expérience *"
           placeholder="Ex: 15"
           outlined
           dense
           clearable
           @blur="validateField('yearsOfExperience')"
         />
       </div>

       <div class="form-group">
         <q-select
           v-model="formData.infiltratedCountryId"
           :options="countries"
           :error="!!errors.infiltratedCountryId"
           :error-message="errors.infiltratedCountryId"
           label="Pays infiltré *"
           placeholder="Sélectionner un pays"
           outlined
           dense
           clearable
           @blur="validateField('infiltratedCountryId')"
         />
       </div>

      <div class="form-actions">
        <q-btn
          type="submit"
          color="primary"
          :loading="isSubmitting"
          :disabled="isSubmitting"
          label="Créer l'agent"
          class="submit-btn"
        />
        <q-btn
          type="button"
          color="secondary"
          outline
          label="Annuler"
          @click="$emit('cancel')"
          class="cancel-btn"
        />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { validateAgent, type AgentForm } from '~/schemas/agent.schema'
import type { CreateAgentDto, CountrySelectOptionDto } from '~/types/dto'
import { agentService } from '~/services/agent.service'
import { useNotification } from '~/composables/useNotification'

// Émettre des événements
const emit = defineEmits<{
  cancel: []
  success: [data: any]
  error: [message: string]
}>()

// Utiliser le composable de notification
const { showSuccess, showError } = useNotification()

// État du formulaire
const formData = reactive<AgentForm>({
  codeName: '',
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  yearsOfExperience: 0,
  infiltratedCountryId: undefined
})

// Liste des pays pour la sélection
const countries = ref<CountrySelectOptionDto[]>([])



const errors = reactive({
  codeName: '',
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  yearsOfExperience: '',
  infiltratedCountryId: ''
})

const isSubmitting = ref(false)

// Récupérer la liste des pays
const fetchCountries = async () => {
  try {
    const result = await agentService.getCountriesForSelect()
    
    if (result.success && result.data) {
      countries.value = result.data
    } else {
      console.error('Erreur lors de la récupération des pays:', result.error?.message)
    }
  } catch (error) {
    console.error('Erreur lors de la récupération des pays:', error)
  }
}

// Charger les pays au montage du composant
onMounted(() => {
  fetchCountries()
})

// Validation d'un champ
const validateField = (field: keyof AgentForm) => {
  const fieldValue = formData[field]
  const partialData = { [field]: fieldValue }
  
  // Validation partielle avec Zod
  const result = validateAgent(partialData)
  
  if (!result.success && result.errors[field]) {
    errors[field] = result.errors[field]
  } else {
    errors[field] = ''
  }
}

// Validation complète du formulaire
const validateForm = (): boolean => {
  const result = validateAgent(formData)
  
  if (!result.success) {
    // Afficher les erreurs
    Object.keys(result.errors).forEach(key => {
      const errorMessage = result.errors[key]
      if (errorMessage) {
        errors[key as keyof typeof errors] = errorMessage
      }
    })
    return false
  }
  
  // Réinitialiser les erreurs si validation réussie
  Object.keys(errors).forEach(key => {
    errors[key as keyof typeof errors] = ''
  })
  
  return true
}

// Soumission du formulaire
const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true

            try {
        // Extraire l'ID du pays si un objet est sélectionné
        const countryId = formData.infiltratedCountryId && typeof formData.infiltratedCountryId === 'object' 
          ? (formData.infiltratedCountryId as { value: number }).value 
          : formData.infiltratedCountryId

        // Créer le DTO pour l'API
        const createAgentDto: CreateAgentDto = {
          codeName: formData.codeName.trim(),
          firstName: formData.firstName.trim(),
          lastName: formData.lastName.trim(),
          email: formData.email.trim(),
          password: formData.password,
          yearsOfExperience: formData.yearsOfExperience,
          infiltratedCountryId: countryId || 0
        }

        const result = await agentService.createAgent(createAgentDto)
        
        if (!result.success) {
          throw new Error(result.error?.message || 'Erreur lors de la création de l\'agent')
        }
        
        const response = { data: result.data }

    // Afficher la notification de succès
    showSuccess(`L'agent "${formData.codeName.trim()}" a été créé avec succès !`)
    
    emit('success', response.data)
    
    // Réinitialiser le formulaire
    resetForm()
    
  } catch (error) {
    console.error('Erreur lors de la création de l\'agent:', error)
    const errorMessage = error instanceof Error ? error.message : 'Erreur lors de la création de l\'agent'
    
    // Afficher la notification d'erreur
    showError(errorMessage)
    
    emit('error', errorMessage)
  } finally {
    isSubmitting.value = false
  }
}

// Réinitialiser le formulaire
const resetForm = () => {
  formData.codeName = ''
  formData.firstName = ''
  formData.lastName = ''
  formData.email = ''
  formData.password = ''
  formData.yearsOfExperience = 0
  formData.infiltratedCountryId = 0
  
  // Réinitialiser les erreurs
  Object.keys(errors).forEach(key => {
    errors[key as keyof typeof errors] = ''
  })
}

// Exposer des méthodes pour le composant parent
defineExpose({
  resetForm
})
</script>