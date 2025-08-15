<template>
  <div class="auth-container">
    <div class="auth-card">
      <h1 class="auth-title">Inscription</h1>
      
      <!-- Message de succès -->
      <div v-if="success" class="success-alert">
        <div class="success-content">
          <h3>Inscription réussie !</h3>
          <p>Rendez-vous sur <a href="http://localhost:8025" target="_blank" class="auth-link">http://localhost:8025</a> pour valider votre email.</p>
          <button 
            @click="navigateTo('/login')" 
            class="auth-button"
          >
            Aller à la page de connexion
          </button>
        </div>
      </div>

      <!-- Formulaire d'inscription -->
      <form v-else @submit.prevent="onSubmit" class="auth-form">
        <div class="form-group">
          <label for="firstName" class="form-label">Prénom</label>
          <input
            id="firstName"
            v-model="form.firstName"
            type="text"
            class="form-input"
            :class="{ 'error': errors.firstName }"
            placeholder="Votre prénom"
            required
            @blur="validateField('firstName')"
          />
          <span v-if="errors.firstName" class="error-message">
            {{ errors.firstName }}
          </span>
        </div>

        <div class="form-group">
          <label for="lastName" class="form-label">Nom</label>
          <input
            id="lastName"
            v-model="form.lastName"
            type="text"
            class="form-input"
            :class="{ 'error': errors.lastName }"
            placeholder="Votre nom"
            required
            @blur="validateField('lastName')"
          />
          <span v-if="errors.lastName" class="error-message">
            {{ errors.lastName }}
          </span>
        </div>

        <div class="form-group">
          <label for="email" class="form-label">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            class="form-input"
            :class="{ 'error': errors.email }"
            placeholder="votre@email.com"
            required
            @blur="validateField('email')"
          />
          <span v-if="errors.email" class="error-message">
            {{ errors.email }}
          </span>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Mot de passe</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            class="form-input"
            :class="{ 'error': errors.password }"
            placeholder="Votre mot de passe"
            required
            @blur="validateField('password')"
          />
          <span v-if="errors.password" class="error-message">
            {{ errors.password }}
          </span>
        </div>

        <!-- Indicateurs de force du mot de passe -->
        <div class="password-strength">
          <div class="strength-label">Force du mot de passe :</div>
          <div class="strength-chips">
            <span 
              :class="[
                'strength-chip',
                form.password.length >= 12 ? 'positive' : 'neutral'
              ]"
            >
              12+ caractères
            </span>
            <span 
              :class="[
                'strength-chip',
                countUppercase(form.password) >= 2 ? 'positive' : 'neutral'
              ]"
            >
              2+ majuscules
            </span>
            <span 
              :class="[
                'strength-chip',
                countDigits(form.password) >= 2 ? 'positive' : 'neutral'
              ]"
            >
              2+ chiffres
            </span>
            <span 
              :class="[
                'strength-chip',
                countSpecialChars(form.password) >= 2 ? 'positive' : 'neutral'
              ]"
            >
              2+ caractères spéciaux
            </span>
          </div>
        </div>

        <!-- Erreur générale -->
        <div v-if="errors.general" class="error-alert">
          {{ errors.general }}
        </div>

        <button 
          type="submit" 
          class="auth-button"
          :disabled="loading"
        >
          <span v-if="loading">Inscription en cours...</span>
          <span v-else>S'inscrire</span>
        </button>
      </form>

      <div class="auth-footer">
        <p>Déjà un compte ? 
          <NuxtLink to="/login" class="auth-link">Se connecter</NuxtLink>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { validateRegistration, type RegistrationForm } from '../schemas/registration.schema'
import { useAuth } from '../composables/useAuth'
import type { CreateUserDto } from '~/types'

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

// Validation d'un champ spécifique (utilisée pour la validation en temps réel)
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
    
    // Créer le DTO pour l'API
    const createUserDto: CreateUserDto = {
      firstName: result.data.firstName,
      lastName: result.data.lastName,
      email: result.data.email,
      password: result.data.password,
      roles: ['ROLE_USER']
    }

    // Appel API via le composable
    const { register } = useAuth()
    const apiResult = await register(createUserDto)
    
    if (apiResult.success && apiResult.data) {
      // Succès
      success.value = true
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

