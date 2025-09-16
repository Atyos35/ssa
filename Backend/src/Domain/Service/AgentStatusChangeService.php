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

    public function handleStatusChange(Agent $agent, ?AgentStatus $previousStatus): void
    {
        $currentStatus = $agent->getStatus();
        
        if ($currentStatus === AgentStatus::KilledInAction && $previousStatus !== AgentStatus::KilledInAction) {
            $this->messageBus->dispatch(new AgentKilledInActionMessage($agent));
        }
    }

    public function getPreviousStatus(Agent $agent): ?AgentStatus
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();
        $originalData = $unitOfWork->getOriginalEntityData($agent);
        
        $statusString = $originalData['status'] ?? null;
        
        if ($statusString === null) {
            return null;
        }
        
        return AgentStatus::from($statusString);
    }
}