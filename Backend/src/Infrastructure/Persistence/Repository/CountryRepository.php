<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository personnalisé pour les pays avec des méthodes de requête optimisées
 */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    /**
     * Trouve tous les pays avec leurs agents
     */
    public function findAllWithAgents(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve un pays avec ses agents et missions
     */
    public function findWithDetails(int $countryId): ?Country
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.missions', 'm')
            ->where('c.id = :countryId')
            ->setParameter('countryId', $countryId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Trouve les pays par niveau de danger
     */
    public function findByDangerLevel(string $dangerLevel): array
    {
        return $this->findBy(['danger' => DangerLevel::from($dangerLevel)]);
    }

    /**
     * Trouve les pays avec des agents infiltrés
     */
    public function findWithInfiltratedAgents(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('EXISTS (SELECT 1 FROM App\Domain\Entity\Agent a WHERE a.infiltratedCountry = c.id)')
            ->orderBy('c.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les pays sans agents infiltrés
     */
    public function findWithoutInfiltratedAgents(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('NOT EXISTS (SELECT 1 FROM App\Domain\Entity\Agent a WHERE a.infiltratedCountry = c.id)')
            ->orderBy('c.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les pays par nombre d'agents
     */
    public function findByAgentCount(int $minCount, int $maxCount = null): array
    {
        // Récupérer tous les pays avec leurs agents chargés
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.agents', 'a')
            ->addSelect('a');
        
        $countries = $qb->getQuery()->getResult();
        
        // Filtrer par nombre d'agents
        $filteredCountries = [];
        foreach ($countries as $country) {
            $agentCount = $country->getAgents()->count();
            if ($agentCount >= $minCount && ($maxCount === null || $agentCount <= $maxCount)) {
                $filteredCountries[] = $country;
            }
        }
        
        return $filteredCountries;
    }
}
