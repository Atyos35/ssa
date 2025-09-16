<?php

namespace App\EventListener;

use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionStatus;
use App\Message\MissionCreatedMessage;
use App\Domain\Service\CountryDangerLevelService;
use App\Domain\Service\MissionResultService;
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
        
        $this->countryDangerLevelService->updateCountryDangerLevelAfterMissionCreation($entity);
        
        $this->messageBus->dispatch(new MissionCreatedMessage($entity));
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Mission) {
            return;
        }
        
        if ($args->hasChangedField('danger')) {
            $this->countryDangerLevelService->updateCountryDangerLevelAfterMissionCreation($entity);
        }
        
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
        
        $this->countryDangerLevelService->updateCountryDangerLevelAfterMissionStatusChange($entity);
    }
}