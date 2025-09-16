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

const emit = defineEmits<{
  cancel: []
  success: [data: any]
  error: [message: string]
}>()

const { showSuccess, showError } = useNotification()

const formData = reactive<AgentForm>({
  codeName: '',
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  yearsOfExperience: 0,
  infiltratedCountryId: undefined
})

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

onMounted(() => {
  fetchCountries()
})

const validateField = (field: keyof AgentForm) => {
  const fieldValue = formData[field]
  const partialData = { [field]: fieldValue }
  
  const result = validateAgent(partialData)
  
  if (!result.success && result.errors[field]) {
    errors[field] = result.errors[field]
  } else {
    errors[field] = ''
  }
}

const validateForm = (): boolean => {
  const result = validateAgent(formData)
  
  if (!result.success) {
    Object.keys(result.errors).forEach(key => {
      const errorMessage = result.errors[key]
      if (errorMessage) {
        errors[key as keyof typeof errors] = errorMessage
      }
    })
    return false
  }
  
  Object.keys(errors).forEach(key => {
    errors[key as keyof typeof errors] = ''
  })
  
  return true
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true

            try {
        const countryId = formData.infiltratedCountryId && typeof formData.infiltratedCountryId === 'object' 
          ? (formData.infiltratedCountryId as { value: number }).value 
          : formData.infiltratedCountryId

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

    showSuccess(`L'agent "${formData.codeName.trim()}" a été créé avec succès !`)
    
    emit('success', response.data)
    
    resetForm()
    
  } catch (error) {
    console.error('Erreur lors de la création de l\'agent:', error)
    const errorMessage = error instanceof Error ? error.message : 'Erreur lors de la création de l\'agent'
    
    showError(errorMessage)
    
    emit('error', errorMessage)
  } finally {
    isSubmitting.value = false
  }
}

const resetForm = () => {
  formData.codeName = ''
  formData.firstName = ''
  formData.lastName = ''
  formData.email = ''
  formData.password = ''
  formData.yearsOfExperience = 0
  formData.infiltratedCountryId = 0
  
  Object.keys(errors).forEach(key => {
    errors[key as keyof typeof errors] = ''
  })
}

defineExpose({
  resetForm
})
</script>