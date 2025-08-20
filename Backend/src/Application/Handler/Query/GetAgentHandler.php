<?php

namespace App\Application\Handler\Query;

use App\Application\Dto\AgentDetailDto;
use App\Application\Handler\QueryHandlerInterface;
use App\Application\Query\GetAgentQuery;
use App\Application\Query\QueryInterface;
use App\Domain\Entity\Agent;
use App\Infrastructure\Persistence\Repository\AgentRepository;

class GetAgentHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly AgentRepository $agentRepository
    ) {}

    public function handle(QueryInterface $query): AgentDetailDto
    {
        if (!$query instanceof GetAgentQuery) {
            throw new \InvalidArgumentException('Expected GetAgentQuery');
        }

        $agent = $this->agentRepository->findWithDetails($query->agentId);
        
        if (!$agent) {
            throw new \DomainException('Agent not found');
        }

        return AgentDetailDto::fromEntity($agent);
    }
} 