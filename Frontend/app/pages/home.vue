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
        
        <q-card class="action-card" @click="openCreateAgentModal">
          <q-card-section class="text-center">
            <q-icon name="person_add" size="48px" color="accent" class="q-mb-md" />
            <h3>Créer de nouveaux agents</h3>
            <div class="text-caption text-grey-6">Recruter de nouveaux membres</div>
          </q-card-section>
        </q-card>
        
        <q-card class="action-card" @click="openCreateMissionModal">
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
      <CountryForm
        @success="handleCountryCreated"
        @error="handleCountryError"
        @cancel="handleCountryModalCancel"
      />
    </Modal>

    <!-- Modal de création d'agent -->
    <Modal v-model="showCreateAgentModal" title="Créer un nouvel agent">
      <AgentForm
        @success="handleAgentCreated"
        @error="handleAgentError"
        @cancel="handleAgentModalCancel"
      />
    </Modal>

    <!-- Modal de création de mission -->
    <Modal v-model="showCreateMissionModal" title="Créer une nouvelle mission">
      <MissionForm
        @success="handleMissionCreated"
        @error="handleMissionError"
        @cancel="handleMissionModalCancel"
      />
    </Modal>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuth } from '~/composables/useAuth'
import Modal from '~/components/Modal.vue'
import CountryForm from '~/components/CountryForm.vue'
import AgentForm from '~/components/AgentForm.vue'
import MissionForm from '~/components/MissionForm.vue'
import { authService } from '~/services/auth.service'
import { useNotification } from '~/composables/useNotification'

// Composables
const { logout } = useAuth()

// Utiliser le composable de notification
const { showSuccess, showError } = useNotification()

// État de la modal
const showCreateCountryModal = ref(false)
const showCreateAgentModal = ref(false)
const showCreateMissionModal = ref(false)

// Ouvrir la modal de création de pays
const openCreateCountryModal = () => {
  showCreateCountryModal.value = true
}

// Ouvrir la modal de création d'agent
const openCreateAgentModal = () => {
  showCreateAgentModal.value = true
}

// Ouvrir la modal de création de mission
const openCreateMissionModal = () => {
  showCreateMissionModal.value = true
}

// Gestion de la déconnexion
const handleLogout = () => {
  logout()
  if (typeof window !== 'undefined') {
    window.location.href = '/login'
  }
}

// Gestion des événements du CountryForm
const handleCountryCreated = () => {
  showCreateCountryModal.value = false
}

// Afficher un message d'erreur à l'utilisateur
const handleCountryError = (error: string) => {
  console.error('Erreur lors de la création du pays:', error)
  showError('Erreur lors de la création du pays.')
}

const handleCountryModalCancel = () => {
  showCreateCountryModal.value = false
}

// Gestion des événements du AgentForm
const handleAgentCreated = () => {
  showCreateAgentModal.value = false
}

// Afficher un message d'erreur à l'utilisateur
const handleAgentError = (error: string) => {
  console.error('Erreur lors de la création de l\'agent:', error)
  showError('Erreur lors de la création de l\'agent.')
}

const handleAgentModalCancel = () => {
  showCreateAgentModal.value = false
}

// Gestion des événements du MissionForm
const handleMissionCreated = () => {
  showCreateMissionModal.value = false
}

// Afficher un message d'erreur à l'utilisateur
const handleMissionError = (error: string) => {
  console.error('Erreur lors de la création de la mission:', error)
  showError('Erreur lors de la création de la mission.')
}

const handleMissionModalCancel = () => {
  showCreateMissionModal.value = false
}

// Vérifier l'authentification au chargement
onMounted(() => {
  // Vérifier directement si l'utilisateur est authentifié
  if (!authService.isAuthenticated()) {
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
  background: white;
  border-radius: 15px;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.action-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.action-card h3 {
  margin: 0;
  color: #333;
  font-size: 1.1rem;
  text-align: center;
}

@media (max-width: 768px) {
  .home-header {
    padding: 40px 20px;
  }
  
  .home-content {
    padding: 20px 20px;
  }
}
</style>
