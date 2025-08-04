<?php

namespace App\Service;

use App\Entity\Mission;
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
     * Gère la création d'une mission et envoie les notifications
     */
    public function handleMissionCreation(Mission $mission): void
    {
        // Persister la mission
        $this->entityManager->persist($mission);
        $this->entityManager->flush();

        // Envoyer le message pour notifier les agents
        $this->messageBus->dispatch(new MissionCreatedMessage($mission));
    }
} 