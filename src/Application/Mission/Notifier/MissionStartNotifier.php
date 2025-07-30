<?php

namespace App\Application\Mission\Notifier;

use App\Entity\Mission;
use App\Entity\Message;
use App\Entity\Agent;
use App\Entity\AgentStatus;
use Doctrine\ORM\EntityManagerInterface;

class MissionStartNotifier
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Notifie tous les agents du pays de la mission (sauf ceux qui participent) lors du début d'une mission.
     */
    public function notifyAgentsOnMissionStart(Mission $mission): void
    {
        $country = $mission->getCountry();
        if (!$country) {
            return; // Pas de pays, pas de notification
        }

        // Récupérer tous les agents infiltrés dans ce pays
        $agentRepo = $this->em->getRepository(Agent::class);
        $agentsInCountry = $agentRepo->createQueryBuilder('a')
            ->where('a.infiltratedCountry = :country')
            ->andWhere('a.status != :killedStatus')
            ->setParameter('country', $country)
            ->setParameter('killedStatus', AgentStatus::KilledInAction)
            ->getQuery()
            ->getResult();

        // Récupérer les IDs des agents qui participent à la mission
        $participatingAgentIds = [];
        foreach ($mission->getAgents() as $participatingAgent) {
            if ($participatingAgent instanceof Agent) {
                $participatingAgentIds[] = $participatingAgent->getId();
            }
        }

        // Envoyer un message à tous les agents du pays sauf ceux qui participent
        foreach ($agentsInCountry as $agent) {
            if (!in_array($agent->getId(), $participatingAgentIds)) {
                $message = new Message();
                $message->setTitle('Nouvelle mission en cours');
                $message->setBody(sprintf(
                    'Une nouvelle mission "%s" a débuté dans votre pays d\'infiltration. ' .
                    'Niveau de danger : %s. Restez vigilant et disponible pour d\'éventuelles demandes de soutien.',
                    $mission->getName(),
                    $mission->getDanger()->value
                ));
                
                // L'auteur du message est le premier agent participant (ou null si aucun)
                $firstParticipatingAgent = $mission->getAgents()->first();
                $message->setBy($firstParticipatingAgent instanceof Agent ? $firstParticipatingAgent : null);
                $message->setRecipient($agent);
                
                $this->em->persist($message);
            }
        }

        $this->em->flush();
    }
} 