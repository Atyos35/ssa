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
import { reactive, computed } from 'vue'
import { useAuth } from '~/composables/useAuth'

const { login, loading, error } = useAuth()

const formData = reactive({
  email: '',
  password: ''
})

const validationErrors = reactive({
  email: '',
  password: ''
})

const authError = computed(() => error.value)

const handleLogin = async () => {
  validationErrors.email = ''
  validationErrors.password = ''

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

  const result = await login({
    email: formData.email,
    password: formData.password
  })

  if (result.success) {
    await navigateTo('/home')
  } else {
    console.error('Erreur de connexion:', result.error?.message)
  }
}
</script>