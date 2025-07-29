<?php

namespace App\Application;

use App\Entity\Mission;
use App\Entity\Agent;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class MissionStartNotifier
{
    private EntityManagerInterface $em;
    private Security $security;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Envoie un message à tous les agents du pays de la mission sauf ceux qui participent à la mission.
     */
    public function notifyAgentsOnMissionStart(Mission $mission): void
    {
        $country = $mission->getCountry();
        if (!$country) {
            return;
        }
        // On récupère les agents par pays
        $agentsInCountry = $this->em->getRepository(\App\Entity\Agent::class)
            ->findBy(['infiltratedCountry' => $country]);
        // Récupération de l'utilisateur courant
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof Agent) {
            throw new \LogicException('L’utilisateur courant n’est pas un agent.');
        }
        $agentsInMission = $mission->getAgents();
        foreach ($agentsInCountry as $agent) {
            if ($agentsInMission->contains($agent)) {
                continue;
            }
            $message = new Message();
            $message->setTitle('Début de mission');
            $message->setBody('Une nouvelle mission a débuté dans votre pays : ' . $mission->getName());
            $message->setBy($currentUser);
            $message->setRecipient($agent);
            $this->em->persist($message);
        }
        $this->em->flush();
    }
}