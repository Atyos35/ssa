<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    public function findAllWithAgents(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findWithDetails(int $countryId): ?Country
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.missions', 'm')
            ->where('c.id = :countryId')
            ->setParameter('countryId', $countryId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByDangerLevel(string $dangerLevel): array
    {
        return $this->findBy(['danger' => DangerLevel::from($dangerLevel)]);
    }

    public function findWithInfiltratedAgents(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('EXISTS (SELECT 1 FROM App\Domain\Entity\Agent a WHERE a.infiltratedCountry = c.id)')
            ->orderBy('c.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findWithoutInfiltratedAgents(): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('NOT EXISTS (SELECT 1 FROM App\Domain\Entity\Agent a WHERE a.infiltratedCountry = c.id)')
            ->orderBy('c.name', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findByAgentCount(int $minCount, int $maxCount = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.agents', 'a')
            ->addSelect('a');
        
        $countries = $qb->getQuery()->getResult();
        
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