<template>
  <q-dialog v-model="isVisible" :backdrop-filter="backdropFilter">
    <q-card class="modal-card" :style="{ width: modalWidth }">
      <q-card-section class="row items-center q-pb-none text-h6">
        {{ title }}
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup @click="closeModal" />
      </q-card-section>
      
      <q-card-section>
        <slot />
      </q-card-section>
      
      <q-card-actions align="right" v-if="$slots.actions">
        <slot name="actions" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue'
interface Props {
  modelValue: boolean
  title: string
  backdropFilter?: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
}

const props = withDefaults(defineProps<Props>(), {
  backdropFilter: 'blur(4px)',
  size: 'md'
})

const emit = defineEmits<Emits>()

const isVisible = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value)
})

const closeModal = () => {
  emit('update:modelValue', false)
}

// Calculer la largeur en fonction de la taille
const modalWidth = computed(() => {
  switch (props.size) {
    case 'sm': return '400px'
    case 'md': return '600px'
    case 'lg': return '900px'
    case 'xl': return '1200px'
    case 'full': return '95vw'
    default: return '600px'
  }
})
</script>

<style scoped>
.modal-card {
  min-width: 400px;
  max-width: 95vw;
  max-height: 90vh;
  overflow-y: auto;
}
</style>

