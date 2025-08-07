<?php

namespace App\Application\Handler\Command;

use App\Application\Command\CommandInterface;
use App\Application\Command\UpdateAgentStatusCommand;
use App\Application\Handler\CommandHandlerInterface;
use App\Domain\Entity\Agent;
use App\Domain\Service\AgentStatusChangeService;
use Doctrine\ORM\EntityManagerInterface;

class UpdateAgentStatusHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AgentStatusChangeService $statusChangeService
    ) {}

    public function handle(CommandInterface $command): void
    {
        if (!$command instanceof UpdateAgentStatusCommand) {
            throw new \InvalidArgumentException('Expected UpdateAgentStatusCommand');
        }

        // Récupérer l'agent
        $agent = $this->entityManager->getRepository(Agent::class)->find($command->agentId);
        if (!$agent) {
            throw new \DomainException('Agent not found');
        }

        $previousStatus = $agent->getStatus();

        // Mettre à jour le statut
        $agent->setStatus($command->status);

        // Persister les changements
        $this->entityManager->persist($agent);
        $this->entityManager->flush();

        // Traiter le changement de statut
        $this->statusChangeService->handleStatusChange($agent, $previousStatus);
    }
} 