<?php

namespace App\Application;

use App\Entity\Agent;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;

class AgentDeathNotifier
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Notifie tous les agents par message lors de la mort d'un agent.
     */
    public function notifyAllAgentsOnDeath(Agent $deadAgent): void
    {
        $agentRepo = $this->em->getRepository(Agent::class);
        $allAgents = $agentRepo->findAll();
        foreach ($allAgents as $agent) {
            if ($agent->getId() === $deadAgent->getId()) {
                continue;
            }
            $message = new Message();
            $message->setTitle('Un agent est mort !');
            $message->setBody('Agent ' . $deadAgent->getCodeName() . " a été tué pendant sa mission !");
            $message->setBy($deadAgent);
            $message->setRecipient($agent);
            $this->em->persist($message);
        }
        $this->em->flush();
    }
} 