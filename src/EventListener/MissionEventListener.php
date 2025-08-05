<?php

namespace App\EventListener;

use App\Entity\Mission;
use App\Service\CountryDangerLevelService;
use Doctrine\ORM\Event\PostPersistEventArgs;

class MissionEventListener
{
    public function __construct(
        private CountryDangerLevelService $countryDangerLevelService
    ) {}

    /**
     * Gère la création d'une mission
     */
    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        
        if (!$entity instanceof Mission) {
            return;
        }

        $this->countryDangerLevelService->updateCountryDangerLevelFromMission($entity);
    }
} 