<template>
  <q-page class="home-page">
    
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

    <div class="home-content">
      
      <div v-if="isLoading" class="loading-section q-mb-lg">
        <q-spinner-dots size="2em" color="primary" />
        <span class="q-ml-sm">Chargement de l'état de la base...</span>
      </div>

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
        
        <q-card class="action-card" @click="openCreateCountryModal">
          <q-card-section class="text-center">
            <q-icon name="public" size="48px" color="warning" class="q-mb-md" />
            <h3>Créer un nouveau pays</h3>
            <div class="text-caption text-grey-6">Ajouter une nouvelle zone d'opération</div>
          </q-card-section>
        </q-card>

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

    <Modal v-model="showCreateCountryModal" title="Créer un nouveau pays">
      <CountryForm
        @success="handleCountryCreated"
        @error="handleCountryError"
        @cancel="handleCountryModalCancel"
      />
    </Modal>

    <Modal v-model="showCreateAgentModal" title="Créer un nouvel agent">
      <AgentForm
        @success="handleAgentCreated"
        @error="handleAgentError"
        @cancel="handleAgentModalCancel"
      />
    </Modal>

    <Modal v-model="showCreateMissionModal" title="Créer une nouvelle mission">
      <MissionForm
        @success="handleMissionCreated"
        @error="handleMissionError"
        @cancel="handleMissionModalCancel"
      />
    </Modal>

    <Modal v-model="showCreateMessageModal" title="Créer un nouveau message">
      <MessageForm
        @success="handleMessageCreated"
        @error="handleMessageError"
        @cancel="handleMessageModalCancel"
      />
    </Modal>

    <Modal v-model="showMissionClosureModal" title="Clôturer une mission">
      <MissionClosureForm
        @success="handleMissionClosureSuccess"
        @error="handleMissionClosureError"
        @cancel="handleMissionClosureModalCancel"
      />
    </Modal>

    <Modal v-model="showMissionListModal" title="Liste des missions et leurs résultats" size="lg">
      <MissionList />
    </Modal>

    <Modal v-model="showAgentInfoModal" title="Informations de l'agent" size="lg">
      <AgentInfoForm />
    </Modal>

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
import { ref, onMounted } from 'vue'
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

const { logout, initUser } = useAuth()
const { isLoading, availableFeatures, isComplete, refreshDatabaseState } = useDatabaseState()

const { showError, showSuccess } = useNotification()

const showCreateCountryModal = ref(false)
const showCreateAgentModal = ref(false)
const showCreateMissionModal = ref(false)
const showCreateMessageModal = ref(false)
const showMissionClosureModal = ref(false)
const showMissionListModal = ref(false)
const showAgentInfoModal = ref(false)
const showKillAgentModal = ref(false)

const openCreateCountryModal = () => {
  showCreateCountryModal.value = true
}

const openCreateAgentModal = () => {
  showCreateAgentModal.value = true
}

const openCreateMissionModal = () => {
  showCreateMissionModal.value = true
}

const openCreateMessageModal = () => {
  showCreateMessageModal.value = true
}

const openMissionClosureModal = () => {
  showMissionClosureModal.value = true
}

const openMissionListModal = () => {
  showMissionListModal.value = true
}

const openAgentInfoModal = () => {
  showAgentInfoModal.value = true
}

const openKillAgentModal = () => {
  showKillAgentModal.value = true
}

const handleLogout = () => {
  logout()
  if (typeof window !== 'undefined') {
    window.location.href = '/login'
  }
}

const handleCountryCreated = async () => {
  showCreateCountryModal.value = false
  
  await refreshDatabaseState()
}

const handleCountryError = () => {
  showError('Erreur lors de la création du pays.')
}

const handleCountryModalCancel = () => {
  showCreateCountryModal.value = false
}

const handleAgentCreated = async () => {
  showCreateAgentModal.value = false
  
  await refreshDatabaseState()
}

const handleAgentError = () => {
  showError('Erreur lors de la création de l\'agent.')
}

const handleAgentModalCancel = () => {
  showCreateAgentModal.value = false
}

const handleMissionCreated = async () => {
  showCreateMissionModal.value = false
  
  await refreshDatabaseState()
}

const handleMissionError = () => {
  showError('Erreur lors de la création de la mission.')
}

const handleMissionModalCancel = () => {
  showCreateMissionModal.value = false
}

const handleMissionClosureSuccess = async () => {
  showMissionClosureModal.value = false
  
  await refreshDatabaseState()
}

const handleMissionClosureError = () => {
  showError('Erreur lors de la clôture de la mission.')
}

const handleMissionClosureModalCancel = () => {
  showMissionClosureModal.value = false
}

const handleAgentKilled = async () => {
  showKillAgentModal.value = false
  
  await refreshDatabaseState()
}

const handleAgentKillError = () => {
  showError('Erreur lors de la mise à mort de l\'agent.')
}

const handleAgentKillModalCancel = () => {
  showKillAgentModal.value = false
}

const handleMessageCreated = async () => {
  showCreateMessageModal.value = false
  showSuccess('Message envoyé avec succès')
}

const handleMessageError = () => {
  showError('Erreur lors de l\'envoi du message.')
}

const handleMessageModalCancel = () => {
  showCreateMessageModal.value = false
}

onMounted(async () => {
  if (!authService.isAuthenticated()) {
    if (typeof window !== 'undefined') {
      window.location.href = '/login'
    }
  } else {
    await initUser()
  }
})

</script>