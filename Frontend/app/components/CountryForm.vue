<template>
  <div class="country-form">
    <form @submit.prevent="handleSubmit">
      <div class="form-group">
        <label for="name" class="form-label">Nom du pays *</label>
        <q-input
          id="name"
          v-model="formData.name"
          :error="!!errors.name"
          :error-message="errors.name"
          placeholder="Ex: France"
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
import { z } from 'zod'
import apiService from '~/services/api.service'
import { useNotification } from '~/composables/useNotification'

// Émettre des événements
const emit = defineEmits<{
  cancel: []
  success: [data: any]
  error: [message: string]
}>()

// Utiliser le composable de notification
const { showSuccess, showError } = useNotification()

// Schéma de validation
const countrySchema = z.object({
  name: z.string()
    .min(2, 'Le nom doit contenir au moins 2 caractères')
    .max(100, 'Le nom ne peut pas dépasser 100 caractères')
    .trim()
})

// État du formulaire
const formData = reactive({
  name: ''
})

const errors = reactive({
  name: ''
})

const isSubmitting = ref(false)

// Validation d'un champ
const validateField = (field: keyof typeof formData) => {
  try {
    countrySchema.parse(formData)
    errors[field] = ''
  } catch (error) {
    if (error instanceof z.ZodError) {
      const fieldError = error.issues.find(issue => issue.path.includes(field))
      if (fieldError) {
        errors[field] = fieldError.message
      }
    }
  }
}

// Validation complète du formulaire
const validateForm = (): boolean => {
  try {
    countrySchema.parse(formData)
    return true
  } catch (error) {
    if (error instanceof z.ZodError) {
      error.issues.forEach(issue => {
        const field = issue.path[0] as keyof typeof formData
        if (field) {
          errors[field] = issue.message
        }
      })
    }
    return false
  }
}

// Soumission du formulaire
const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true

  try {
    const response = await apiService.post('/api/countries', {
      name: formData.name.trim()
    })

    // Afficher la notification de succès
    showSuccess(`Le pays "${formData.name.trim()}" a été créé avec succès !`)
    
    emit('success', response.data)
    
    // Réinitialiser le formulaire
    formData.name = ''
    errors.name = ''
    
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

<style scoped>
.country-form {
  padding: 1rem 0;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #333;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
}

.submit-btn {
  min-width: 120px;
}

.cancel-btn {
  min-width: 100px;
}

/* Responsive */
@media (max-width: 768px) {
  .form-actions {
    flex-direction: column;
  }
  
  .submit-btn,
  .cancel-btn {
    width: 100%;
  }
}
</style>
