<?php

namespace App\MessageHandler;

use App\Domain\Entity\Agent;
use App\Domain\Entity\Message;
use App\Message\AgentKilledInActionMessage;
use App\Infrastructure\Persistence\Repository\AgentRepository;
use App\Infrastructure\Persistence\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AgentKilledInActionMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AgentRepository $agentRepository,
        private readonly MessageRepository $messageRepository
    ) {
    }

    public function __invoke(AgentKilledInActionMessage $message): void
    {
        $killedAgent = $message->getKilledAgent();
        
        $killedAgentFromDb = $this->agentRepository->find($killedAgent->getId());
        if (!$killedAgentFromDb) {
            throw new \RuntimeException('Agent not found in database');
        }
        
        $this->deleteAgentMessages($killedAgentFromDb);
        
        $allAgents = $this->agentRepository->findAll();
        
        foreach ($allAgents as $agent) {
            if ($agent->getId() === $killedAgentFromDb->getId()) {
                continue;
            }
            
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
            $notificationMessage->setBy($killedAgentFromDb);
            
            $this->entityManager->persist($notificationMessage);
        }
        
        $this->entityManager->flush();
    }

    private function deleteAgentMessages(Agent $agent): void
    {
        $this->messageRepository->deleteAllByAgent($agent);
    }
}