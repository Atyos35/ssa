// Composable pour l'authentification
import { ref, computed, readonly } from 'vue'
import { authService } from '../services/auth.service'
import type { RegisterRequest } from '../types/api'
import type { LoginRequest } from '../services/auth.service'

export const useAuth = () => {
  // État réactif
  const user = ref<{
    id: string
    email: string
    firstName: string
    lastName: string
    roles: string[]
  } | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed properties
  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.roles?.includes('ROLE_ADMIN') || false)

  // Inscription
  const register = async (data: RegisterRequest) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await authService.register(data)
      
      if (response.success) {
        // Inscription réussie
        return { success: true, data: response.data }
      } else {
        // Gérer les erreurs de validation
        if (response.error?.errors) {
          const validationErrors: Record<string, string> = {}
          Object.entries(response.error.errors).forEach(([field, messages]) => {
            if (field && messages && messages.length > 0 && messages[0]) {
              validationErrors[field] = messages[0] // Prendre le premier message d'erreur
            }
          })
          return { success: false, validationErrors }
        }
        
        // Erreur générale
        error.value = response.error?.message || 'Erreur lors de l\'inscription'
        return { success: false, error: response.error?.message || 'Erreur lors de l\'inscription' }
      }
    } catch (err) {
      error.value = 'Erreur réseau lors de l\'inscription'
      return { success: false, error: 'Erreur réseau lors de l\'inscription' }
    } finally {
      loading.value = false
    }
  }

  // Connexion
  const login = async (data: LoginRequest) => {
    loading.value = true
    error.value = null
    
    try {
      const response = await authService.login(data)
      
      if (response.success && response.data) {
        user.value = response.data.user
        return { success: true, user: response.data.user }
      } else {
        error.value = response.error?.message || 'Identifiants invalides'
        return { success: false, error: error.value }
      }
    } catch (err) {
      error.value = 'Erreur réseau lors de la connexion'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  // Déconnexion
  const logout = () => {
    authService.logout()
    user.value = null
    error.value = null
  }

  // Vérifier l'état d'authentification au chargement
  const checkAuth = () => {
    if (authService.isAuthenticated()) {
      // TODO: Vérifier la validité du token avec l'API
      // Pour l'instant, on considère que l'utilisateur est connecté
    }
  }

  // Initialiser l'état d'authentification
  checkAuth()

  return {
    // État
    user: readonly(user),
    loading: readonly(loading),
    error: readonly(error),
    
    // Computed
    isAuthenticated,
    isAdmin,
    
    // Méthodes
    register,
    login,
    logout,
    checkAuth
  }
}
