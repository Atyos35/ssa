<?php

namespace App\MessageHandler;

use App\Entity\Agent;
use App\Entity\Message;
use App\Message\MissionCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class MissionCreatedMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(MissionCreatedMessage $message): void
    {
        $mission = $message->getMission();
        
        // Récupérer la mission depuis la base de données pour le contexte de persistance
        $missionFromDb = $this->entityManager->getRepository(\App\Entity\Mission::class)->find($mission->getId());
        if (!$missionFromDb) {
            throw new \RuntimeException('Mission not found in database');
        }
        
        $country = $missionFromDb->getCountry();
        if (!$country) {
            throw new \RuntimeException('Mission has no country');
        }
        
        // Récupérer tous les agents infiltrés dans ce pays
        $agentsInCountry = $this->entityManager->getRepository(Agent::class)->findBy([
            'infiltratedCountry' => $country
        ]);
        
        // Récupérer les IDs des agents qui participent à la mission
        $participatingAgentIds = [];
        foreach ($missionFromDb->getAgents() as $participatingAgent) {
            $participatingAgentIds[] = $participatingAgent->getId();
        }
        
        foreach ($agentsInCountry as $agent) {
            // Ne pas envoyer de message aux agents qui participent à la mission
            if (in_array($agent->getId(), $participatingAgentIds)) {
                continue;
            }
            
            // Créer un message de notification pour chaque agent
            $notificationMessage = new Message();
            $notificationMessage->setTitle('Nouvelle Mission Créée');
            $notificationMessage->setBody(
                sprintf(
                    'Une nouvelle mission "%s" a été créée dans votre pays d\'infiltration. ' .
                    'Niveau de danger : %s. ' .
                    'Objectifs : %s. ' .
                    'Date de début : %s.',
                    $missionFromDb->getName(),
                    $missionFromDb->getDanger()->value,
                    $missionFromDb->getObjectives(),
                    $missionFromDb->getStartDate()->format('d/m/Y')
                )
            );
            $notificationMessage->setRecipient($agent);
            // Pas d'expéditeur spécifique pour les notifications de mission
            $notificationMessage->setBy(null);
            
            $this->entityManager->persist($notificationMessage);
        }
        
        $this->entityManager->flush();
    }
} 