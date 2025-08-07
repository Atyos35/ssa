<?php

namespace App\Domain\Service;

use App\Domain\Entity\Mission;
use App\Message\MissionCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class MissionCreationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    /**
     * Gère la création d'une mission
     * Note: L'envoi du message est maintenant géré par l'événement postPersist
     */
    public function handleMissionCreation(Mission $mission): void
    {
        // Persister la mission
        $this->entityManager->persist($mission);
        $this->entityManager->flush();
    }
} 
