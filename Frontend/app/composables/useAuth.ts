import { ref, computed } from 'vue'
import { authService } from '~/services/auth.service'
import type { LoginRequest, RegisterRequest } from '~/services/auth.service'
import type { UserDto } from '~/types/dto'

export const useAuth = () => {
  const user = ref<UserDto | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.roles?.includes('ROLE_ADMIN') || false)

  const register = async (data: RegisterRequest) => {
    loading.value = true
    error.value = null
    
    try {
      const result = await authService.register(data)
      
      if (result.success && result.data) {
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

  const login = async (data: LoginRequest) => {
    loading.value = true
    error.value = null
    
    try {
      const result = await authService.login(data)
      
      if (result.success && result.data) {
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

  const logout = () => {
    authService.logout()
    user.value = null
    error.value = null
  }

  const getAuthToken = () => {
    return authService.getToken()
  }

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
    user,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    register,
    login,
    logout,
    getAuthToken,
    initUser
  }
}