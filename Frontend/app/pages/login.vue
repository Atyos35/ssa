<template>
  <div class="auth-container">
    <div class="auth-card">
      <h1 class="auth-title">Connexion</h1>
      
      <form @submit.prevent="handleLogin" class="auth-form">
        <div class="form-group">
          <label for="email" class="form-label">Email</label>
          <input
            id="email"
            v-model="formData.email"
            type="email"
            class="form-input"
            :class="{ 'error': validationErrors.email }"
            placeholder="votre@email.com"
            required
          />
          <span v-if="validationErrors.email" class="error-message">
            {{ validationErrors.email }}
          </span>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">Mot de passe</label>
          <input
            id="password"
            v-model="formData.password"
            type="password"
            class="form-input"
            :class="{ 'error': validationErrors.password }"
            placeholder="Votre mot de passe"
            required
          />
          <span v-if="validationErrors.password" class="error-message">
            {{ validationErrors.password }}
          </span>
        </div>

        <!-- Message d'erreur général -->
        <div v-if="authError" class="error-alert">
          {{ authError }}
        </div>

        <button 
          type="submit" 
          class="auth-button"
          :disabled="loading"
        >
          <span v-if="loading">Connexion en cours...</span>
          <span v-else>Se connecter</span>
        </button>
      </form>

      <div class="auth-footer">
        <p>Pas encore de compte ? 
          <NuxtLink to="/registration" class="auth-link">S'inscrire</NuxtLink>
        </p>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useAuth } from '~/composables/useAuth'

// Utiliser le composable d'authentification
const { login, loading, error } = useAuth()

// Données du formulaire
const formData = reactive({
  email: '',
  password: ''
})

// Erreurs de validation
const validationErrors = reactive({
  email: '',
  password: ''
})

// Erreur d'authentification
const authError = computed(() => error.value)

// Gestion de la soumission du formulaire
const handleLogin = async () => {
  // Réinitialiser les erreurs
  validationErrors.email = ''
  validationErrors.password = ''

  // Validation côté client
  let hasErrors = false
  
  if (!formData.email) {
    validationErrors.email = 'L\'email est requis'
    hasErrors = true
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
    validationErrors.email = 'Format d\'email invalide'
    hasErrors = true
  }

  if (!formData.password) {
    validationErrors.password = 'Le mot de passe est requis'
    hasErrors = true
  }

  if (hasErrors) {
    return
  }

  // Tentative de connexion
  const result = await login({
    email: formData.email,
    password: formData.password
  })

  if (result.success) {
    // Redirection vers la page d'accueil
    await navigateTo('/home')
  } else {
    // L'erreur est déjà gérée par le composable useAuth
    // et sera affichée via authError
  }
}
</script>

<style scoped>
/* Les styles sont maintenant globaux dans assets/css/global.css */
</style>
