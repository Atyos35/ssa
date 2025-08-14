<template>
  <q-page class="home-page">
    <!-- Header avec bouton de déconnexion -->
    <div class="home-header">
      <div class="header-content">
        <div class="header-top">
          <q-btn
            color="white"
            text-color="negative"
            icon="logout"
            label="Déconnexion"
            @click="handleLogout"
            class="logout-btn"
            size="md"
          />
        </div>
        <h1 class="text-h2 text-white text-weight-bold q-mb-md">
          Tableau de bord SSA
        </h1>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="home-content">
      <div class="actions-grid">
        <q-card class="action-card" @click="openCreateCountryModal">
          <q-card-section class="text-center">
            <q-icon name="list_alt" size="48px" color="primary" class="q-mb-md" />
            <h3>Voir la liste des missions et leurs résultats</h3>
            <div class="text-caption text-grey-6">Consulter toutes les missions et leurs résultats</div>
          </q-card-section>
        </q-card>
        
        <q-card class="action-card" @click="openCreateCountryModal">
          <q-card-section class="text-center">
            <q-icon name="person" size="48px" color="secondary" class="q-mb-md" />
            <h3>Voir les informations d'un agent, ses missions et ses messages</h3>
            <div class="text-caption text-grey-6">Ses missions et ses messages</div>
          </q-card-section>
        </q-card>
        
        <q-card class="action-card" @click="openCreateCountryModal">
          <q-card-section class="text-center">
            <q-icon name="person_add" size="48px" color="accent" class="q-mb-md" />
            <h3>Créer de nouveaux agents</h3>
            <div class="text-caption text-grey-6">Recruter de nouveaux membres</div>
          </q-card-section>
        </q-card>
        
        <q-card class="action-card" @click="openCreateCountryModal">
          <q-card-section class="text-center">
            <q-icon name="add_task" size="48px" color="positive" class="q-mb-md" />
            <h3>Créer de nouvelles missions</h3>
            <div class="text-caption text-grey-6">Planifier de nouvelles opérations</div>
          </q-card-section>
        </q-card>
        
        <q-card class="action-card" @click="openCreateCountryModal">
          <q-card-section class="text-center">
            <q-icon name="message" size="48px" color="info" class="q-mb-md" />
            <h3>Créer un nouveau message</h3>
            <div class="text-caption text-grey-6">Envoyer une communication</div>
          </q-card-section>
        </q-card>
        
        <q-card class="action-card" @click="openCreateCountryModal">
          <q-card-section class="text-center">
            <q-icon name="public" size="48px" color="warning" class="q-mb-md" />
            <h3>Créer un nouveau pays</h3>
            <div class="text-caption text-grey-6">Ajouter une nouvelle zone d'opération</div>
          </q-card-section>
        </q-card>
        
        <q-card class="action-card" @click="openCreateCountryModal">
          <q-card-section class="text-center">
            <q-icon name="task_alt" size="48px" color="deep-orange" class="q-mb-md" />
            <h3>Clôturer une mission et remplir les informations Résultat de mission</h3>
            <div class="text-caption text-grey-6">Finaliser une opération</div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Modal de création de pays -->
    <Modal v-model="showCreateCountryModal" title="Créer un nouveau pays">
      <div class="modal-content">
        <p>Cette modal utilise un backdrop filter de {{ backdropFilter }}.</p>
      </div>
      
      <template #actions>
        <q-btn flat label="Fermer" color="primary" v-close-popup />
      </template>
    </Modal>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuth } from '~/composables/useAuth'
import Modal from '~/components/Modal.vue'

// Composables
const { isAuthenticated, logout } = useAuth()

// État de la modal
const showCreateCountryModal = ref(false)
const backdropFilter = ref('blur(4px)')

// Ouvrir la modal de création de pays
const openCreateCountryModal = () => {
  backdropFilter.value = 'blur(4px)'
  showCreateCountryModal.value = true
}

// Gestion de la déconnexion
const handleLogout = () => {
  logout()
  if (typeof window !== 'undefined') {
    window.location.href = '/login'
  }
}

// Vérifier l'authentification au chargement
onMounted(() => {
  if (!isAuthenticated.value) {
    if (typeof window !== 'undefined') {
      window.location.href = '/login'
    }
  }
})
</script>

<style scoped>
.home-page {
  background: #f5f5f5;
  min-height: 100vh;
}

.home-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 60px 20px;
  margin-bottom: 0;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  text-align: center;
  position: relative;
}

.header-top {
  position: absolute;
  top: 0;
  right: 0;
}

.logout-btn {
  border-radius: 8px;
  font-weight: 500;
}

.home-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 20px;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.action-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 15px;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.action-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  background: rgba(255, 255, 255, 1);
}

.action-card h3 {
  margin: 0;
  color: #333;
  font-size: 1.1rem;
  text-align: center;
}

.modal-content {
  padding: 1rem 0;
}

/* Responsive */
@media (max-width: 768px) {
  .home-header {
    padding: 40px 20px;
  }
  
  .header-content h1 {
    font-size: 2rem !important;
  }
  
  .home-content {
    padding: 20px 20px;
  }
}
</style>
