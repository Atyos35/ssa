<?php

namespace App\EventListener;

use App\Entity\Mission;
use App\Entity\MissionStatus;
use App\Message\MissionCreatedMessage;
use App\Service\CountryDangerLevelService;
use App\Service\MissionResultService;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Symfony\Component\Messenger\MessageBusInterface;

final class MissionEventListener
{
    public function __construct(
        private CountryDangerLevelService $countryDangerLevelService,
        private MissionResultService $missionResultService,
        private MessageBusInterface $messageBus
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Mission) {
            return;
        }
        
        // Mettre à jour le niveau de danger du pays
        $this->countryDangerLevelService->updateCountryDangerLevelAfterMissionCreation($entity);
        
        // Envoyer le message pour notifier les agents
        $this->messageBus->dispatch(new MissionCreatedMessage($entity));
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Mission) {
            return;
        }
        
        // Si le danger change, on met à jour immédiatement
        if ($args->hasChangedField('danger')) {
            $this->countryDangerLevelService->updateCountryDangerLevelAfterMissionCreation($entity);
        }
        
        // Si le statut change vers Success ou Failure, créer un résultat de mission
        if ($args->hasChangedField('status')) {
            $this->missionResultService->createMissionResult($entity);
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Mission) {
            return;
        }
        
        // Si le statut change, on met à jour après flush
        $this->countryDangerLevelService->updateCountryDangerLevelAfterMissionStatusChange($entity);
    }
} 