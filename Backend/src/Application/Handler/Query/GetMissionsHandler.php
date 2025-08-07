<?php

namespace App\Application\Handler\Query;

use App\Application\Dto\MissionDto;
use App\Application\Handler\QueryHandlerInterface;
use App\Application\Query\GetMissionsQuery;
use App\Application\Query\QueryInterface;
use App\Domain\Entity\Mission;
use Doctrine\ORM\EntityManagerInterface;

class GetMissionsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function handle(QueryInterface $query): array
    {
        if (!$query instanceof GetMissionsQuery) {
            throw new \InvalidArgumentException('Expected GetMissionsQuery');
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('m')
           ->from(Mission::class, 'm')
           ->leftJoin('m.country', 'c')
           ->leftJoin('m.agents', 'a')
           ->leftJoin('m.missionResult', 'mr');

        // Appliquer les filtres
        if ($query->status !== null) {
            $qb->andWhere('m.status = :status')
               ->setParameter('status', $query->status);
        }

        if ($query->danger !== null) {
            $qb->andWhere('m.danger = :danger')
               ->setParameter('danger', $query->danger);
        }

        if ($query->countryId !== null) {
            $qb->andWhere('c.id = :countryId')
               ->setParameter('countryId', $query->countryId);
        }

        // Pagination
        $offset = ($query->page - 1) * $query->limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($query->limit);

        // Tri par date de création (plus récent en premier)
        $qb->orderBy('m.id', 'DESC');

        $missions = $qb->getQuery()->getResult();

        return array_map(fn(Mission $mission) => MissionDto::fromEntity($mission), $missions);
    }
} 