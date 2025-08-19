// Composable d'authentification
import { ref, computed } from 'vue'
import { authService } from '~/services/auth.service'
import type { LoginRequest, RegisterRequest } from '~/services/auth.service'
import type { UserDto } from '~/types/dto'

export const useAuth = () => {
  // État réactif
  const user = ref<UserDto | null>(null)
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
      const result = await authService.register(data)
      
      if (result.success && result.data) {
        // Connexion automatique après inscription
        await login({
          email: data.email,
          password: data.password
        })
      } else {
        error.value = result.error?.message || 'Erreur lors de l\'inscription'
      }
      
      return result
    } catch (err) {
      error.value = 'Erreur lors de l\'inscription'
      return {
        success: false,
        error: {
          message: error.value,
          status: 500
        }
      }
    } finally {
      loading.value = false
    }
  }

  // Connexion
  const login = async (data: LoginRequest) => {
    loading.value = true
    error.value = null
    
    try {
      const result = await authService.login(data)
      
      if (result.success && result.data) {
        // Mettre à jour l'état utilisateur avec les données de la requête
        user.value = {
          id: 'user_from_token',
          email: data.email,
          firstName: 'Utilisateur',
          lastName: 'Connecté',
          roles: ['ROLE_USER']
        }
        error.value = null
      } else {
        error.value = result.error?.message || 'Identifiants incorrects'
      }
      
      return result
    } catch (err) {
      error.value = 'Erreur réseau lors de la connexion'
      return {
        success: false,
        error: {
          message: error.value,
          status: 500
        }
      }
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

  // Récupérer le token d'authentification
  const getAuthToken = () => {
    return authService.getToken()
  }

  // Initialiser l'utilisateur au montage si un token existe
  const initUser = async () => {
    try {
      const response = await authService.getCurrentUser()
      if (response.success && response.data) {
        user.value = response.data
        // isAuthenticated.value = true // This line was removed from the new_code, so it's removed here.
      }
    } catch (error) {
      console.error('Erreur lors de l\'initialisation de l\'utilisateur:', error)
    }
  }

  return {
    // État
    user,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    // Méthodes
    register,
    login,
    logout,
    getAuthToken,
    initUser
  }
}
