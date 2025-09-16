<?php

namespace App\EventListener;

use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Domain\Service\AgentStatusChangeService;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::postUpdate)]
class AgentStatusChangeListener
{
    public function __construct(
        private readonly AgentStatusChangeService $statusChangeService
    ) {
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        
        if (!$entity instanceof Agent) {
            return;
        }
        
        if ($args->hasChangedField('status')) {
            $previousStatus = $args->getOldValue('status');
            $currentStatus = $args->getNewValue('status');
            
            if ($currentStatus === AgentStatus::KilledInAction && $previousStatus !== AgentStatus::KilledInAction) {
                $args->getObjectManager()->getUnitOfWork()->setOriginalEntityData(
                    $entity,
                    ['status' => $previousStatus]
                );
            }
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        
        if (!$entity instanceof Agent) {
            return;
        }
        
        $currentStatus = $entity->getStatus();
        
        if ($currentStatus === AgentStatus::KilledInAction) {
            $previousStatus = $this->statusChangeService->getPreviousStatus($entity);
            
            if ($previousStatus !== AgentStatus::KilledInAction) {
                $this->statusChangeService->handleStatusChange($entity, $previousStatus);
            }
        }
    }
}