<?php

namespace App\Application\Handler\Query;

use App\Application\Dto\AgentDetailDto;
use App\Application\Handler\QueryHandlerInterface;
use App\Application\Query\GetAgentQuery;
use App\Application\Query\QueryInterface;
use App\Domain\Entity\Agent;
use Doctrine\ORM\EntityManagerInterface;

class GetAgentHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function handle(QueryInterface $query): AgentDetailDto
    {
        if (!$query instanceof GetAgentQuery) {
            throw new \InvalidArgumentException('Expected GetAgentQuery');
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
           ->from(Agent::class, 'a')
           ->leftJoin('a.infiltratedCountry', 'c')
           ->leftJoin('a.mentor', 'm')
           ->leftJoin('a.missions', 'missions')
           ->leftJoin('a.messages', 'messages')
           ->where('a.id = :agentId')
           ->setParameter('agentId', $query->agentId);

        $agent = $qb->getQuery()->getOneOrNullResult();
        
        if (!$agent) {
            throw new \DomainException('Agent not found');
        }

        return AgentDetailDto::fromEntity($agent);
    }
} 