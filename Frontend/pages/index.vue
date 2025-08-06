<template>
  <div class="q-pa-md">
    <q-card class="my-card">
      <q-card-section>
        <div class="text-h6">SSA - Secret Service Application</div>
        <div class="text-subtitle2">Frontend avec Quasar + Nuxt 3 + Vue 3 + TypeScript</div>
      </q-card-section>

      <q-card-section>
        <p>Bienvenue dans votre application de service secret !</p>
        <p>Cette page utilise :</p>
        <ul>
          <li>✅ Quasar Framework (dernière version)</li>
          <li>✅ Nuxt 3 (dernière version)</li>
          <li>✅ Vue 3 avec Composition API</li>
          <li>✅ TypeScript</li>
          <li>✅ Tailwind CSS</li>
        </ul>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn 
          color="primary" 
          label="Test Notification" 
          @click="showNotification"
        />
        <q-btn 
          color="secondary" 
          label="Test Dialog" 
          @click="showDialog"
        />
      </q-card-actions>
    </q-card>

    <div class="q-mt-md">
      <q-btn 
        color="positive" 
        icon="check" 
        label="Tout fonctionne !" 
        class="full-width"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Notify, Dialog } from 'quasar'

const showNotification = () => {
  Notify.create({
    type: 'positive',
    message: 'Notification Quasar fonctionne !',
    icon: 'check_circle'
  })
}

const showDialog = () => {
  Dialog.create({
    title: 'Test Dialog',
    message: 'Le système de dialogue Quasar fonctionne parfaitement !',
    ok: {
      label: 'OK',
      color: 'primary'
    }
  })
}
</script>

<style scoped>
.my-card {
  max-width: 600px;
  margin: 0 auto;
}
</style> 