<?php

namespace App\Application\Handler\Query;

use App\Application\Dto\AgentDto;
use App\Application\Handler\QueryHandlerInterface;
use App\Application\Query\GetAgentsQuery;
use App\Application\Query\QueryInterface;
use App\Domain\Entity\Agent;
use Doctrine\ORM\EntityManagerInterface;

class GetAgentsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function handle(QueryInterface $query): array
    {
        if (!$query instanceof GetAgentsQuery) {
            throw new \InvalidArgumentException('Expected GetAgentsQuery');
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('a')
           ->from(Agent::class, 'a')
           ->leftJoin('a.infiltratedCountry', 'c')
           ->leftJoin('a.mentor', 'm');

        // Appliquer les filtres
        if ($query->status !== null) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $query->status);
        }

        if ($query->countryId !== null) {
            $qb->andWhere('c.id = :countryId')
               ->setParameter('countryId', $query->countryId);
        }

        // Pagination
        $offset = ($query->page - 1) * $query->limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($query->limit);

        // Tri par nom de code
        $qb->orderBy('a.codeName', 'ASC');

        $agents = $qb->getQuery()->getResult();

        return array_map(fn(Agent $agent) => AgentDto::fromEntity($agent), $agents);
    }
} 