<template>
  <div>
    <ClientOnly>
      <q-layout view="hHh lpR fFf">
        <Header />
        
        <q-page-container>
          <NuxtPage />
        </q-page-container>
        
        <Footer />
      </q-layout>
    </ClientOnly>
  </div>
</template>

<script setup lang="ts">
import Header from './components/Header.vue'
import Footer from './components/Footer.vue'
</script>
