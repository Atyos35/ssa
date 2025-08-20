<?php

namespace App\Application\Handler\Query;

use App\Application\Dto\AgentDto;
use App\Application\Handler\QueryHandlerInterface;
use App\Application\Query\GetAgentsQuery;
use App\Application\Query\QueryInterface;
use App\Domain\Entity\Agent;
use App\Infrastructure\Persistence\Repository\AgentRepository;

class GetAgentsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly AgentRepository $agentRepository
    ) {}

    public function handle(QueryInterface $query): array
    {
        if (!$query instanceof GetAgentsQuery) {
            throw new \InvalidArgumentException('Expected GetAgentsQuery');
        }

        $agents = $this->agentRepository->findWithFilters(
            $query->status,
            $query->countryId,
            $query->page,
            $query->limit
        );

        return array_map(fn(Agent $agent) => AgentDto::fromEntity($agent), $agents);
    }
} 