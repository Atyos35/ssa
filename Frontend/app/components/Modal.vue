<template>
  <q-dialog v-model="isVisible" :backdrop-filter="backdropFilter">
    <q-card class="modal-card">
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
interface Props {
  modelValue: boolean
  title: string
  backdropFilter?: string
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void
}

const props = withDefaults(defineProps<Props>(), {
  backdropFilter: 'blur(4px)'
})

const emit = defineEmits<Emits>()

const isVisible = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value)
})

const closeModal = () => {
  emit('update:modelValue', false)
}
</script>

<style scoped>
.modal-card {
  min-width: 400px;
  max-width: 90vw;
}
</style>

