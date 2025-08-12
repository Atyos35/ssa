<template>
  <div class="q-pa-md">
    <q-card class="registration-card">
      <q-card-section class="text-center">
        <h2 class="text-h4 q-mb-md">Inscription</h2>
      </q-card-section>

      <q-card-section>
        <!-- Message de succès -->
        <div v-if="success" class="text-center q-mb-md">
          <q-banner class="text-positive">
            <template v-slot:avatar>
              <q-icon name="check_circle" color="positive" />
            </template>
            {{ successMessage }}
          </q-banner>
          
          <div class="q-mt-md">
            <q-btn
              color="primary"
              label="Aller à la page de connexion"
              @click="navigateTo('/login')"
            />
          </div>
        </div>

        <!-- Formulaire d'inscription -->
        <q-form v-else-if="!success" @submit="onSubmit" class="q-gutter-md">
          <!-- Prénom -->
          <q-input
            v-model="form.firstName"
            label="Prénom *"
            outlined
            :error="!!errors.firstName"
            :error-message="errors.firstName"
            @blur="validateField('firstName')"
          />

          <!-- Nom -->
          <q-input
            v-model="form.lastName"
            label="Nom *"
            outlined
            :error="!!errors.lastName"
            :error-message="errors.lastName"
            @blur="validateField('lastName')"
          />

          <!-- Email -->
          <q-input
            v-model="form.email"
            label="Email *"
            type="email"
            outlined
            :error="!!errors.email"
            :error-message="errors.email"
            @blur="validateField('email')"
          />

          <!-- Mot de passe -->
          <q-input
            v-model="form.password"
            label="Mot de passe *"
            type="password"
            outlined
            :error="!!errors.password"
            :error-message="errors.password"
            @blur="validateField('password')"
          />

          <!-- Indicateurs de force du mot de passe -->
          <div class="password-strength q-mt-sm">
            <div class="text-caption q-mb-xs">Force du mot de passe :</div>
            <div class="row q-gutter-xs">
              <q-chip 
                :color="form.password.length >= 12 ? 'positive' : 'grey'" 
                size="sm"
                :label="`12+ caractères`"
              />
              <q-chip 
                :color="countUppercase(form.password) >= 2 ? 'positive' : 'grey'" 
                size="sm"
                :label="`2+ majuscules`"
              />
              <q-chip 
                :color="countDigits(form.password) >= 2 ? 'positive' : 'grey'" 
                size="sm"
                :label="`2+ chiffres`"
              />
              <q-chip 
                :color="countSpecialChars(form.password) >= 2 ? 'positive' : 'grey'" 
                size="sm"
                :label="`2+ caractères spéciaux`"
              />
            </div>
          </div>

          <!-- Bouton de soumission -->
          <div class="q-mt-lg">
            <!-- Erreur générale -->
            <div v-if="errors.general" class="q-mb-md">
              <q-banner class="text-negative">
                <template v-slot:avatar>
                  <q-icon name="error" color="negative" />
                </template>
                {{ errors.general }}
              </q-banner>
            </div>
            
            <q-btn
              type="submit"
              color="primary"
              size="lg"
              class="full-width"
              :loading="loading"
              label="S'inscrire"
            />
          </div>
        </q-form>
      </q-card-section>
    </q-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { validateRegistration, type RegistrationForm } from '../schemas/registration.schema'
import { useAuth } from '../composables/useAuth'

// État du formulaire
const form = reactive<RegistrationForm>({
  firstName: '',
  lastName: '',
  email: '',
  password: ''
})

// État de l'interface
const loading = ref(false)
const success = ref(false)
const successMessage = ref('')
const errors = reactive({
  firstName: '',
  lastName: '',
  email: '',
  password: '',
  general: '' // Erreur générale du formulaire
})

// Validation des champs (pour les indicateurs visuels)
const countUppercase = (str: string): number => {
  return (str.match(/[A-Z]/g) || []).length
}

const countDigits = (str: string): number => {
  return (str.match(/\d/g) || []).length
}

const countSpecialChars = (str: string): number => {
  return (str.match(/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/g) || []).length
}

// Validation d'un champ spécifique
const validateField = (fieldName: keyof RegistrationForm) => {
  const fieldValue = form[fieldName]
  const partialData = { [fieldName]: fieldValue }
  
  // Validation partielle avec Zod
  const result = validateRegistration(partialData)
  
  if (!result.success && result.errors[fieldName]) {
    errors[fieldName] = result.errors[fieldName]
  } else {
    errors[fieldName] = ''
  }
}

// Soumission du formulaire
const onSubmit = async () => {
  loading.value = true
  
  try {
    // Validation complète avec Zod
    const result = validateRegistration(form)
    
    if (!result.success) {
      // Afficher les erreurs
      Object.keys(result.errors).forEach(key => {
        const errorMessage = result.errors[key]
        if (errorMessage) {
          errors[key as keyof typeof errors] = errorMessage
        }
      })
      return
    }
    
    // Réinitialiser les erreurs
    Object.keys(errors).forEach(key => {
      errors[key as keyof typeof errors] = ''
    })
    
    // Appel API via le composable
    const { register } = useAuth()
    const apiResult = await register({
      firstName: result.data.firstName,
      lastName: result.data.lastName,
      email: result.data.email,
      password: result.data.password,
      roles: ['ROLE_USER']
    })
    
    if (apiResult.success && apiResult.data) {
      // Succès
      success.value = true
      successMessage.value = `Inscription réussie ! Bienvenue ${apiResult.data.message}.`

      // Rediriger vers la page de connexion
      //navigateTo('/login')
      
    } else if (apiResult.validationErrors) {
      // Erreurs de validation côté serveur
      Object.entries(apiResult.validationErrors).forEach(([field, message]) => {
        if (field in errors) {
          errors[field as keyof typeof errors] = message
        }
      })
    } else {
      // Erreur générale
      errors.general = `Inscription échouée ! ${apiResult.error}`
    }
    
  } catch (error) {
    console.error('Erreur lors de l\'inscription :', error)
  } finally {
    loading.value = false
  }
}

// Fonction de navigation
const navigateTo = (path: string) => {
  window.location.href = path
}
</script>

<style scoped>
.registration-card {
  max-width: 500px;
  margin: 0 auto;
}

.password-strength {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 12px;
  background-color: #fafafa;
}
</style>