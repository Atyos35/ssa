<?php

namespace App\MessageHandler;

use App\Domain\Entity\Agent;
use App\Domain\Entity\Message;
use App\Message\MissionCreatedMessage;
use App\Infrastructure\Persistence\Repository\AgentRepository;
use App\Infrastructure\Persistence\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class MissionCreatedMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AgentRepository $agentRepository,
        private readonly MissionRepository $missionRepository
    ) {
    }

    public function __invoke(MissionCreatedMessage $message): void
    {
        $mission = $message->getMission();
        
        $missionFromDb = $this->missionRepository->find($mission->getId());
        if (!$missionFromDb) {
            throw new \RuntimeException('Mission not found in database');
        }
        
        $country = $missionFromDb->getCountry();
        if (!$country) {
            return;
        }
        
        $agentsInCountry = $this->agentRepository->findByCountry($country->getId());
        
        $participatingAgentIds = [];
        foreach ($missionFromDb->getAgents() as $participatingAgent) {
            $participatingAgentIds[] = $participatingAgent->getId();
        }
        
        foreach ($agentsInCountry as $agent) {
            if (in_array($agent->getId(), $participatingAgentIds)) {
                continue;
            }
            
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
            $notificationMessage->setBy(null);
            
            $this->entityManager->persist($notificationMessage);
        }
        
        $this->entityManager->flush();
    }
}