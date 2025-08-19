<template>
  <div class="message-form">
    <form @submit.prevent="handleSubmit">
      <div class="form-group">
        <q-select
          v-model="formData.recipient"
          :options="recipientOptions"
          :error="!!errors.recipient"
          :error-message="errors.recipient"
          label="Destinataire *"
          placeholder="Sélectionner un agent"
          outlined
          dense
          clearable
          emit-value
          map-options
          @blur="validateField('recipient')"
        />
      </div>

      <div class="form-group">
        <q-input
          v-model="formData.title"
          :error="!!errors.title"
          :error-message="errors.title"
          placeholder="Titre du message *"
          outlined
          dense
          @blur="validateField('title')"
        />
      </div>

      <div class="form-group">
        <q-input
          v-model="formData.body"
          type="textarea"
          :error="!!errors.body"
          :error-message="errors.body"
          placeholder="Contenu du message *"
          outlined
          dense
          rows="4"
          @blur="validateField('body')"
        />
      </div>

      <div class="form-actions">
        <q-btn
          type="submit"
          color="primary"
          :loading="isSubmitting"
          label="Envoyer le message"
          class="q-mr-md"
        />
        <q-btn
          type="button"
          color="grey"
          label="Annuler"
          @click="$emit('cancel')"
          outline
        />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuth } from '~/composables/useAuth'
import { messageService } from '~/services/message.service'
import { agentService } from '~/services/agent.service'
import { useNotification } from '~/composables/useNotification'

// Émettre les événements
const emit = defineEmits<{
  success: []
  error: [message: string]
  cancel: []
}>()

// Composables
const { user } = useAuth()
const { showSuccess, showError } = useNotification()

// État du formulaire
const formData = ref({
  recipient: '',
  title: '',
  body: ''
})

// État des erreurs
const errors = ref<Record<string, string>>({})

// État de soumission
const isSubmitting = ref(false)

// Options pour le destinataire
const recipientOptions = ref<Array<{ label: string; value: string }>>([])

// Charger la liste des agents (sauf l'agent connecté)
const loadAgents = async () => {
  try {
    const response = await agentService.getAgents()
    if (response.success && response.data) {
      recipientOptions.value = response.data
        .filter((agent: any) => agent.id !== user.value?.id)
        .map((agent: any) => ({
          label: agent.codeName,
          value: agent.id
        }))
    }
  } catch (error) {
    console.error('Erreur lors du chargement des agents:', error)
  }
}

// Validation des champs
const validateField = (field: string) => {
  const value = formData.value[field as keyof typeof formData.value]
  
  switch (field) {
    case 'recipient':
      if (!value) {
        errors.value[field] = 'Le destinataire est requis'
      } else {
        delete errors.value[field]
      }
      break
    case 'title':
      if (!value) {
        errors.value[field] = 'Le titre est requis'
      } else if (value.length < 2) {
        errors.value[field] = 'Le titre doit contenir au moins 2 caractères'
      } else if (value.length > 100) {
        errors.value[field] = 'Le titre ne peut pas dépasser 100 caractères'
      } else {
        delete errors.value[field]
      }
      break
    case 'body':
      if (!value) {
        errors.value[field] = 'Le contenu du message est requis'
      } else if (value.length < 1) {
        errors.value[field] = 'Le contenu du message est requis'
      } else if (value.length > 1000) {
        errors.value[field] = 'Le contenu ne peut pas dépasser 1000 caractères'
      } else {
        delete errors.value[field]
      }
      break
  }
}

// Validation complète du formulaire
const validateForm = (): boolean => {
  validateField('recipient')
  validateField('title')
  validateField('body')
  
  return Object.keys(errors.value).length === 0
}

// Soumission du formulaire
const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  isSubmitting.value = true

  try {
    const messageData = {
      title: formData.value.title,
      body: formData.value.body,
      recipient: `/api/users/${formData.value.recipient}`,
      by: user.value ? `/api/users/${user.value.id}` : undefined
    }

    const response = await messageService.createMessage(messageData)

    if (response.success) {
      showSuccess('Message envoyé avec succès')
      emit('success')
      
      // Réinitialiser le formulaire
      formData.value = {
        recipient: '',
        title: '',
        body: ''
      }
      errors.value = {}
    } else {
      showError(response.error?.message || 'Erreur lors de l\'envoi du message')
      emit('error', response.error?.message || 'Erreur lors de l\'envoi du message')
    }
  } catch (error) {
    console.error('Erreur lors de l\'envoi du message:', error)
    showError('Erreur lors de l\'envoi du message')
    emit('error', 'Erreur lors de l\'envoi du message')
  } finally {
    isSubmitting.value = false
  }
}

// Charger les agents au montage du composant
onMounted(() => {
  loadAgents()
})
</script>

<style scoped>
.message-form {
  padding: 1rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1.5rem;
}
</style>
