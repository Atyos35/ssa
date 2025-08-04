<?php

namespace App\MessageHandler;

use App\Entity\Agent;
use App\Entity\Message;
use App\Message\AgentKilledInActionMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AgentKilledInActionMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(AgentKilledInActionMessage $message): void
    {
        $killedAgent = $message->getKilledAgent();
        
        // Récupérer l'agent tué depuis la base de données pour le contexte de persistance
        $killedAgentFromDb = $this->entityManager->getRepository(Agent::class)->find($killedAgent->getId());
        if (!$killedAgentFromDb) {
            throw new \RuntimeException('Agent not found in database');
        }
        
        // Supprimer tous les messages de l'agent tué
        $this->deleteAgentMessages($killedAgentFromDb);
        
        // Récupérer tous les agents (sauf celui qui vient d'être tué)
        $allAgents = $this->entityManager->getRepository(Agent::class)->findAll();
        
        foreach ($allAgents as $agent) {
            // Ne pas envoyer de message à l'agent tué
            if ($agent->getId() === $killedAgentFromDb->getId()) {
                continue;
            }
            
            // Créer un message de notification pour chaque agent
            $notificationMessage = new Message();
            $notificationMessage->setTitle('Agent Killed in Action');
            $notificationMessage->setBody(
                sprintf(
                    'Nous avons le regret de vous informer que l\'agent "%s" a été tué en mission. ' .
                    'Nos pensées vont à sa famille et à ses proches. ' .
                    'Restez vigilants et prenez soin de vous.',
                    $killedAgentFromDb->getCodeName()
                )
            );
            $notificationMessage->setRecipient($agent);
            $notificationMessage->setBy($killedAgentFromDb); // L'agent tué est l'expéditeur symbolique
            
            $this->entityManager->persist($notificationMessage);
        }
        
        $this->entityManager->flush();
    }

    /**
     * Supprime tous les messages liés à un agent
     */
    private function deleteAgentMessages(Agent $agent): void
    {
        // Supprimer les messages reçus par l'agent (destinataire)
        $receivedMessages = $this->entityManager->getRepository(Message::class)->findBy(['recipient' => $agent]);
        foreach ($receivedMessages as $message) {
            $this->entityManager->remove($message);
        }
        
        // Supprimer les messages envoyés par l'agent (expéditeur)
        $sentMessages = $this->entityManager->getRepository(Message::class)->findBy(['by' => $agent]);
        foreach ($sentMessages as $message) {
            $this->entityManager->remove($message);
        }
    }
} 