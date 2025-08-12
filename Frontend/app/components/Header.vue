<template>
  <q-header elevated class="bg-primary text-white">
    <q-toolbar>
      <q-toolbar-title>
        <q-avatar>
          <img src="https://cdn.quasar.dev/logo-v2/svg/logo-mono-white.svg" alt="Logo">
        </q-avatar>
        SSA - Secret Service Agency
      </q-toolbar-title>
      
      <q-btn flat round dense icon="menu" aria-label="Menu" />
    </q-toolbar>
  </q-header>
</template>

<script setup lang="ts">
// Pas besoin d'imports, les composants Quasar sont globaux
</script>

<style scoped>
.q-toolbar-title {
  font-size: 1.2rem;
  font-weight: 600;
}
</style>
