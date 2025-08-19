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
      <!-- Indicateur de chargement de l'état de la base -->
      <div v-if="isLoading" class="loading-section q-mb-lg">
        <q-spinner-dots size="2em" color="primary" />
        <span class="q-ml-sm">Chargement de l'état de la base...</span>
      </div>
      
            <!-- Message informatif sur la progression -->
      <div v-if="!isLoading && !isComplete" class="info-section q-mb-lg">
        <q-banner class="bg-info text-white">
          <template v-slot:avatar>
            <q-icon name="info" />
          </template>
          <div v-if="!availableFeatures.canCreateAgent">
            <strong>Première étape :</strong> Créez un pays pour commencer à recruter des agents.
          </div>
          <div v-else-if="!availableFeatures.canCreateMission">
            <strong>Deuxième étape :</strong> Créez un agent pour pouvoir planifier des missions.
          </div>
          <div v-else-if="!availableFeatures.canViewMissionList">
            <strong>Troisième étape :</strong> Créez une mission pour accéder à la gestion complète.
          </div>
        </q-banner>
      </div>
      
      <div class="actions-grid">
        <!-- 1. Créer un pays - Toujours disponible -->
        <q-card class="action-card" @click="openCreateCountryModal">
          <q-card-section class="text-center">
            <q-icon name="public" size="48px" color="warning" class="q-mb-md" />
            <h3>Créer un nouveau pays</h3>
            <div class="text-caption text-grey-6">Ajouter une nouvelle zone d'opération</div>
          </q-card-section>
        </q-card>
        
        <!-- 2. Créer un agent - Disponible seulement s'il y a des pays -->
        <q-card 
          v-if="availableFeatures.canCreateAgent"
          class="action-card" 
          @click="openCreateAgentModal"
        >
          <q-card-section class="text-center">
            <q-icon name="person_add" size="48px" color="accent" class="q-mb-md" />
            <h3>Créer de nouveaux agents</h3>
            <div class="text-caption text-grey-6">Recruter de nouveaux membres</div>
          </q-card-section>
        </q-card>
        
        <!-- 3. Créer une mission - Disponible seulement s'il y a des agents -->
        <q-card 
          v-if="availableFeatures.canCreateMission"
          class="action-card" 
          @click="openCreateMissionModal"
        >
          <q-card-section class="text-center">
            <q-icon name="add_task" size="48px" color="positive" class="q-mb-md" />
            <h3>Créer de nouvelles missions</h3>
            <div class="text-caption text-grey-6">Planifier de nouvelles opérations</div>
          </q-card-section>
        </q-card>
        
        <!-- 4. Voir les informations d'un agent - Disponible seulement s'il y a des agents -->
        <q-card 
          v-if="availableFeatures.canViewAgentInfo"
          class="action-card" 
          @click="openAgentInfoModal"
        >
          <q-card-section class="text-center">
            <q-icon name="info" size="48px" color="teal" class="q-mb-md" />
            <h3>Voir les informations d'un agent</h3>
            <div class="text-caption text-grey-6">Consulter les détails, missions et messages d'un agent</div>
          </q-card-section>
        </q-card>
        
        <!-- 5. Tuer un agent - Disponible seulement s'il y a des agents -->
        <q-card 
          v-if="availableFeatures.canKillAgent"
          class="action-card" 
          @click="openKillAgentModal"
        >
          <q-card-section class="text-center">
            <q-icon name="person_off" size="48px" color="negative" class="q-mb-md" />
            <h3>Tuer un agent</h3>
            <div class="text-caption text-grey-6">Déclarer un agent comme tué en action</div>
          </q-card-section>
        </q-card>
        
        <!-- 6. Voir la liste des missions - Disponible seulement s'il y a des missions -->
        <q-card 
          v-if="availableFeatures.canViewMissionList"
          class="action-card" 
          @click="openMissionListModal"
        >
          <q-card-section class="text-center">
            <q-icon name="list" size="48px" color="purple" class="q-mb-md" />
            <h3>Voir la liste des missions</h3>
            <div class="text-caption text-grey-6">Consulter toutes les missions et leurs résultats</div>
          </q-card-section>
        </q-card>
        
        <!-- 7. Clôturer une mission - Disponible seulement s'il y a des missions -->
        <q-card 
          v-if="availableFeatures.canCloseMission"
          class="action-card" 
          @click="openMissionClosureModal"
        >
          <q-card-section class="text-center">
            <q-icon name="task_alt" size="48px" color="deep-orange" class="q-mb-md" />
            <h3>Clôturer une mission et remplir les informations Résultat de mission</h3>
            <div class="text-caption text-grey-6">Finaliser une opération</div>
          </q-card-section>
        </q-card>

        <!-- 8. Créer un nouveau message - Disponible seulement s'il y a des agents -->
        <q-card 
          v-if="availableFeatures.canCreateAgent"
          class="action-card" 
          @click="openCreateMessageModal"
        >
          <q-card-section class="text-center">
            <q-icon name="mail" size="48px" color="indigo" class="q-mb-md" />
            <h3>Créer un nouveau message</h3>
            <div class="text-caption text-grey-6">Envoyer un message interne à un agent</div>
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

    <!-- Modal de création de message -->
    <Modal v-model="showCreateMessageModal" title="Créer un nouveau message">
      <MessageForm
        @success="handleMessageCreated"
        @error="handleMessageError"
        @cancel="handleMessageModalCancel"
      />
    </Modal>

    <!-- Modal de clôture de mission -->
    <Modal v-model="showMissionClosureModal" title="Clôturer une mission">
      <MissionClosureForm
        @success="handleMissionClosureSuccess"
        @error="handleMissionClosureError"
        @cancel="handleMissionClosureModalCancel"
      />
    </Modal>

    <!-- Modal de liste des missions -->
    <Modal v-model="showMissionListModal" title="Liste des missions et leurs résultats" size="lg">
      <MissionList @viewMission="handleViewMission" />
    </Modal>

    <!-- Modal d'informations d'agent -->
    <Modal v-model="showAgentInfoModal" title="Informations de l'agent" size="lg">
      <AgentInfoForm />
    </Modal>

    <!-- Modal pour tuer un agent -->
    <Modal v-model="showKillAgentModal" title="Tuer un agent">
      <KillAgentForm
        @success="handleAgentKilled"
        @error="handleAgentKillError"
        @cancel="handleAgentKillModalCancel"
      />
    </Modal>


  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useAuth } from '~/composables/useAuth'
import { useDatabaseState } from '~/composables/useDatabaseState'
import Modal from '~/components/Modal.vue'
import CountryForm from '~/components/CountryForm.vue'
import AgentForm from '~/components/AgentForm.vue'
import MissionForm from '~/components/MissionForm.vue'
import MissionClosureForm from '~/components/MissionClosureForm.vue'
import MissionList from '~/components/MissionList.vue'
import AgentInfoForm from '~/components/AgentInfoForm.vue'
import KillAgentForm from '~/components/KillAgentForm.vue'
import MessageForm from '~/components/MessageForm.vue'
import { authService } from '~/services/auth.service'
import { useNotification } from '~/composables/useNotification'

// Composables
const { logout, user, checkAuth, initUser } = useAuth()
const { isLoading, availableFeatures, isComplete, refreshDatabaseState } = useDatabaseState()

// Utiliser le composable de notification
const { showError, showSuccess } = useNotification()

// État de la modal
const showCreateCountryModal = ref(false)
const showCreateAgentModal = ref(false)
const showCreateMissionModal = ref(false)
const showCreateMessageModal = ref(false)
const showMissionClosureModal = ref(false)
const showMissionListModal = ref(false)
const showAgentInfoModal = ref(false)
const showKillAgentModal = ref(false)

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

// Ouvrir la modal de création de message
const openCreateMessageModal = () => {
  showCreateMessageModal.value = true
}

// Ouvrir la modal de clôture de mission
const openMissionClosureModal = () => {
  showMissionClosureModal.value = true
}

// Ouvrir la modal de liste des missions
const openMissionListModal = () => {
  showMissionListModal.value = true
}

// Ouvrir la modal d'informations d'agent
const openAgentInfoModal = () => {
  showAgentInfoModal.value = true
}

// Ouvrir la modal de mise à mort d'agent
const openKillAgentModal = () => {
  showKillAgentModal.value = true
}



// Gestion de la déconnexion
const handleLogout = () => {
  logout()
  if (typeof window !== 'undefined') {
    window.location.href = '/login'
  }
}

// Gestion des événements du CountryForm
const handleCountryCreated = async () => {
  showCreateCountryModal.value = false
  
  // Rafraîchir l'état de la base pour débloquer les fonctionnalités
  await refreshDatabaseState()
}

// Afficher un message d'erreur à l'utilisateur
const handleCountryError = (error: string) => {
  showError('Erreur lors de la création du pays.')
}

const handleCountryModalCancel = () => {
  showCreateCountryModal.value = false
}

// Gestion des événements du AgentForm
const handleAgentCreated = async () => {
  showCreateAgentModal.value = false
  
  // Rafraîchir l'état de la base pour débloquer les fonctionnalités
  await refreshDatabaseState()
}

// Afficher un message d'erreur à l'utilisateur
const handleAgentError = (error: string) => {
  showError('Erreur lors de la création de l\'agent.')
}

const handleAgentModalCancel = () => {
  showCreateAgentModal.value = false
}

// Gestion des événements du MissionForm
const handleMissionCreated = async () => {
  showCreateMissionModal.value = false
  
  // Rafraîchir l'état de la base pour débloquer les fonctionnalités
  await refreshDatabaseState()
}

// Afficher un message d'erreur à l'utilisateur
const handleMissionError = (error: string) => {
  showError('Erreur lors de la création de la mission.')
}

const handleMissionModalCancel = () => {
  showCreateMissionModal.value = false
}

// Gestion des événements du MissionClosureForm
const handleMissionClosureSuccess = async () => {
  showMissionClosureModal.value = false
  
  // Rafraîchir l'état de la base
  await refreshDatabaseState()
}

// Afficher un message d'erreur à l'utilisateur
const handleMissionClosureError = (error: string) => {
  showError('Erreur lors de la clôture de la mission.')
}

const handleMissionClosureModalCancel = () => {
  showMissionClosureModal.value = false
}

// Gestion de la vue d'une mission
const handleViewMission = (mission: any) => {
  // Ici vous pouvez ajouter la logique pour afficher plus de détails
  // Par exemple, ouvrir une autre modal avec les détails complets
}

// Gestion des événements du KillAgentModal
const handleAgentKilled = async () => {
  showKillAgentModal.value = false
  
  // Rafraîchir l'état de la base
  await refreshDatabaseState()
}

const handleAgentKillError = (error: string) => {
  showError('Erreur lors de la mise à mort de l\'agent.')
}

const handleAgentKillModalCancel = () => {
  showKillAgentModal.value = false
}

// Gestion des événements du MessageForm
const handleMessageCreated = async () => {
  showCreateMessageModal.value = false
  showSuccess('Message envoyé avec succès')
}

const handleMessageError = (error: string) => {
  showError('Erreur lors de l\'envoi du message.')
}

const handleMessageModalCancel = () => {
  showCreateMessageModal.value = false
}

// Vérifier l'authentification au chargement
onMounted(async () => {
  // Vérifier directement si l'utilisateur est authentifié
  if (!authService.isAuthenticated()) {
    if (typeof window !== 'undefined') {
      window.location.href = '/login'
    }
  } else {
    // Initialiser l'utilisateur dans le composable useAuth
    await initUser()
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
