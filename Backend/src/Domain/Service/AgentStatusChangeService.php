<?php

namespace App\Domain\Service;

use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Message\AgentKilledInActionMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class AgentStatusChangeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    /**
     * Vérifie si le statut d'un agent a changé pour Killed in Action
     */
    public function handleStatusChange(Agent $agent, ?AgentStatus $previousStatus): void
    {
        $currentStatus = $agent->getStatus();
        
        // Si le statut est passé à "Killed in Action"
        if ($currentStatus === AgentStatus::KilledInAction && $previousStatus !== AgentStatus::KilledInAction) {
            // Dispatcher le message pour notifier tous les agents
            $this->messageBus->dispatch(new AgentKilledInActionMessage($agent));
        }
    }

    /**
     * Récupère le statut précédent d'un agent depuis la base de données
     */
    public function getPreviousStatus(Agent $agent): ?AgentStatus
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $originalData = $unitOfWork->getOriginalEntityData($agent);
        
        $statusString = $originalData['status'] ?? null;
        
        if ($statusString === null) {
            return null;
        }
        
        // Convertir la chaîne en enum AgentStatus
        return AgentStatus::from($statusString);
    }
} 
