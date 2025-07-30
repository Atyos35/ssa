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

    /**
     * Supprime tous les messages de l'agent mort.
     */
    public function deleteAllMessagesOfAgent(Agent $deadAgent): void
    {
        $messageRepo = $this->em->getRepository(Message::class);
        
        // Supprimer tous les messages où l'agent est destinataire
        $messagesAsRecipient = $messageRepo->createQueryBuilder('m')
            ->where('m.recipient = :agent')
            ->setParameter('agent', $deadAgent)
            ->getQuery()
            ->getResult();
            
        foreach ($messagesAsRecipient as $message) {
            $this->em->remove($message);
        }
        
        // Supprimer tous les messages où l'agent est auteur
        $messagesAsAuthor = $messageRepo->createQueryBuilder('m')
            ->where('m.by = :agent')
            ->setParameter('agent', $deadAgent)
            ->getQuery()
            ->getResult();
            
        foreach ($messagesAsAuthor as $message) {
            $this->em->remove($message);
        }
        
        $this->em->flush();
    }
} 