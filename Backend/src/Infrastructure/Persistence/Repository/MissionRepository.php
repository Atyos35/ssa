<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionStatus;
use App\Domain\Entity\DangerLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository personnalisé pour les missions avec des méthodes de requête optimisées
 */
class MissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mission::class);
    }

    /**
     * Trouve les missions avec filtres et pagination
     */
    public function findWithFilters(?string $status, ?string $danger, ?int $countryId, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.country', 'c')
            ->leftJoin('m.agents', 'a')
            ->leftJoin('m.missionResult', 'mr');

        // Appliquer les filtres
        if ($status !== null) {
            $qb->andWhere('m.status = :status')
               ->setParameter('status', MissionStatus::from($status));
        }

        if ($danger !== null) {
            $qb->andWhere('m.danger = :danger')
               ->setParameter('danger', DangerLevel::from($danger));
        }

        if ($countryId !== null) {
            $qb->andWhere('c.id = :countryId')
               ->setParameter('countryId', $countryId);
        }

        // Pagination
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        // Tri par date de création (plus récent en premier)
        $qb->orderBy('m.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve une mission avec tous ses détails
     */
    public function findWithDetails(int $missionId): ?Mission
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.country', 'c')
            ->leftJoin('m.agents', 'a')
            ->leftJoin('m.missionResult', 'mr')
            ->where('m.id = :missionId')
            ->setParameter('missionId', $missionId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve les missions en cours
     */
    public function findInProgressMissions(): array
    {
        return $this->findBy(['status' => MissionStatus::InProgress]);
    }

    /**
     * Trouve les missions par pays
     */
    public function findByCountry(int $countryId): array
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.country', 'c')
            ->where('c.id = :countryId')
            ->setParameter('countryId', $countryId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les missions par niveau de danger
     */
    public function findByDangerLevel(string $dangerLevel): array
    {
        return $this->findBy(['danger' => DangerLevel::from($dangerLevel)]);
    }

    /**
     * Trouve les missions actives (en cours ou planifiées)
     */
    public function findActiveMissions(): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.status IN (:statuses)')
            ->setParameter('statuses', [MissionStatus::InProgress, MissionStatus::Planned]);

        return $qb->getQuery()->getResult();
    }
}

