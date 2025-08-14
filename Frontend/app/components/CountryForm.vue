<template>
  <form @submit.prevent="handleSubmit" class="country-form">
    <!-- Nom du pays -->
    <div class="form-group">
      <label for="countryName" class="form-label">Nom du pays *</label>
      <q-input
        id="countryName"
        v-model="formData.name"
        type="text"
        outlined
        dense
        :error="!!errors.name"
        :error-message="errors.name"
        placeholder="Entrez le nom du pays"
        @blur="validateField('name')"
      />
    </div>

    <!-- Responsable (CellLeader) -->
    <div class="form-group">
      <label for="countryCellLeader" class="form-label">Responsable</label>
      <q-select
        id="countryCellLeader"
        v-model="formData.cellLeader"
        :options="agents"
        option-label="name"
        option-value="id"
        outlined
        dense
        clearable
        :error="!!errors.cellLeader"
        :error-message="errors.cellLeader"
        placeholder="Sélectionnez un responsable (optionnel)"
        @blur="validateField('cellLeader')"
      />
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { z } from 'zod'

// Props
interface Props {
  onSubmit?: (data: any) => void
  onValidationError?: (errors: any) => void
}

// Emits
interface Emits {
  (e: 'form-data-change', data: CountryFormData): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Schéma de validation Zod
const countrySchema = z.object({
  name: z.string()
    .min(2, 'Le nom doit contenir au moins 2 caractères')
    .max(100, 'Le nom ne peut pas dépasser 100 caractères'),
  cellLeader: z.any().optional()
})

type CountryFormData = z.infer<typeof countrySchema>

// État local
const formData = reactive<CountryFormData>({
  name: '',
  cellLeader: null
})
const errors = reactive<Partial<CountryFormData>>({})
const agents = ref<Array<{ id: string, name: string }>>([])

// Validation d'un champ
const validateField = (field: keyof CountryFormData) => {
  try {
    const fieldSchema = countrySchema.pick({ [field]: true })
    fieldSchema.parse({ [field]: formData[field] })
    delete errors[field]
  } catch (error) {
    if (error instanceof z.ZodError && error.issues.length > 0 && error.issues[0]) {
      errors[field] = error.issues[0].message
    }
  }
  
  // Émettre les changements de données
  emit('form-data-change', formData)
}

// Charger la liste des agents
const loadAgents = async () => {
  try {
    const token = localStorage.getItem('auth_token')
    if (!token) {
      throw new Error('Token d\'authentification manquant')
    }

    const response = await fetch('http://127.0.0.1:8000/api/agents', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    })

    if (!response.ok) {
      throw new Error('Erreur lors du chargement des agents')
    }

    const data = await response.json()
    if (data['hydra:member'] && Array.isArray(data['hydra:member'])) {
      agents.value = data['hydra:member'].map((agent: any) => ({
        id: agent.id,
        name: agent.name
      }))
    } else {
      agents.value = []
    }
  } catch (error) {
    console.error('Erreur lors du chargement des agents:', error)
  }
}

// Soumission du formulaire
const handleSubmit = async () => {
  // Valider le formulaire
  try {
    countrySchema.parse(formData)
  } catch (error) {
    if (error instanceof z.ZodError) {
      error.issues.forEach((err: any) => {
        const field = err.path[0] as keyof CountryFormData
        errors[field] = err.message
      })
    }
    
    if (props.onValidationError) {
      props.onValidationError(errors)
    }
    return
  }

  if (props.onSubmit) {
    props.onSubmit(formData)
  }
}

// Réinitialiser le formulaire
const resetForm = () => {
  formData.name = ''
  formData.cellLeader = null
  Object.keys(errors).forEach(key => delete errors[key as keyof CountryFormData])
}

// Exposer les méthodes publiques
defineExpose({
  resetForm,
  validateField,
  formData,
  errors
})

// Initialisation
onMounted(() => {
  loadAgents()
})
</script>

<style scoped>
.country-form {
  padding: 20px 0;
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
  margin-bottom: 8px;
}
</style>
