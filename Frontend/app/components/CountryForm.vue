<template>
  <div class="country-form">
    <form @submit.prevent="handleSubmit">
      <div class="form-group">
        <q-input
          v-model="formData.name"
          :error="!!errors.name"
          :error-message="errors.name"
          placeholder="Nom du pays *"
          outlined
          dense
          @blur="validateField('name')"
        />
      </div>

      <div class="form-actions">
        <q-btn
          type="submit"
          color="primary"
          :loading="isSubmitting"
          :disabled="isSubmitting"
          label="Créer le pays"
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
import { ref, reactive } from 'vue'
import { validateCountry, type CountryForm } from '~/schemas/country.schema'
import type { CreateCountryDto } from '~/types/dto'
import { countryService } from '~/services/country.service'
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
const formData = reactive<CountryForm>({
  name: ''
})

const errors = reactive({
  name: ''
})

const isSubmitting = ref(false)

// Validation d'un champ
const validateField = (field: keyof CountryForm) => {
  const fieldValue = formData[field]
  const partialData = { [field]: fieldValue }
  
  // Validation partielle avec Zod
  const result = validateCountry(partialData)
  
  if (!result.success && result.errors[field]) {
    errors[field] = result.errors[field]
  } else {
    errors[field] = ''
  }
}

// Validation complète du formulaire
const validateForm = (): boolean => {
  const result = validateCountry(formData)
  
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
    // Créer le DTO pour l'API
    const createCountryDto: CreateCountryDto = {
      name: formData.name.trim()
    }

    const result = await countryService.createCountry(createCountryDto)
    
    if (!result.success) {
      throw new Error(result.error?.message || 'Erreur lors de la création du pays')
    }
    
    const response = { data: result.data }

    // Afficher la notification de succès
    showSuccess(`Le pays "${formData.name.trim()}" a été créé avec succès !`)
    
    emit('success', response.data)
    
    // Réinitialiser le formulaire
    resetForm()
    
  } catch (error) {
    console.error('Erreur lors de la création du pays:', error)
    const errorMessage = error instanceof Error ? error.message : 'Erreur lors de la création du pays'
    
    // Afficher la notification d'erreur
    showError(errorMessage)
    
    emit('error', errorMessage)
  } finally {
    isSubmitting.value = false
  }
}

// Réinitialiser le formulaire
const resetForm = () => {
  formData.name = ''
  errors.name = ''
}

// Exposer des méthodes pour le composant parent
defineExpose({
  resetForm
})
</script>