<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AgentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agent::class);
    }

    public function findWithFilters(?string $status, ?int $countryId, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.infiltratedCountry', 'c')
            ->leftJoin('a.mentor', 'm');

        if ($status !== null) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', AgentStatus::from($status));
        }

        if ($countryId !== null) {
            $qb->andWhere('c.id = :countryId')
               ->setParameter('countryId', $countryId);
        }

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        $qb->orderBy('a.codeName', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findWithDetails(int $agentId): ?Agent
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.infiltratedCountry', 'c')
            ->leftJoin('a.mentor', 'm')
            ->leftJoin('a.missions', 'missions')
            ->leftJoin('a.messages', 'messages')
            ->where('a.id = :agentId')
            ->setParameter('agentId', $agentId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByCodeName(string $codeName): ?Agent
    {
        return $this->findOneBy(['codeName' => $codeName]);
    }

    public function findAvailableAgents(): array
    {
        return $this->findBy(['status' => AgentStatus::Available]);
    }

    public function findByCountry(int $countryId): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.infiltratedCountry', 'c')
            ->where('c.id = :countryId')
            ->setParameter('countryId', $countryId);

        return $qb->getQuery()->getResult();
    }
}