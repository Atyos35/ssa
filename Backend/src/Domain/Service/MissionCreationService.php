<?php

namespace App\Domain\Service;

use App\Domain\Entity\Mission;
use App\Message\MissionCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MissionCreationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    /**
     * Gère la création d'une mission
     */
    public function handleMissionCreation(Mission $mission): void
    {
        $this->entityManager->persist($mission);
        $this->entityManager->flush();
    }
} 
