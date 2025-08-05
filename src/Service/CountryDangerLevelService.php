<?php

namespace App\Service;

use App\Entity\Country;
use App\Entity\DangerLevel;
use App\Entity\Mission;
use App\Entity\MissionStatus;
use Doctrine\ORM\EntityManagerInterface;

class CountryDangerLevelService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Met à jour le niveau de danger d'un pays en fonction de ses missions actives
     */
    public function updateCountryDangerLevel(Country $country): void
    {
        $highestDangerLevel = $this->getHighestDangerLevelFromActiveMissions($country);
        $country->setDanger($highestDangerLevel);
        
        $this->entityManager->persist($country);
        $this->entityManager->flush();
    }

    /**
     * Récupère le niveau de danger le plus élevé parmi les missions actives d'un pays
     */
    private function getHighestDangerLevelFromActiveMissions(Country $country): ?DangerLevel
    {
        $activeMissions = $this->entityManager->getRepository(Mission::class)
            ->createQueryBuilder('m')
            ->where('m.country = :country')
            ->andWhere('m.status = :status')
            ->setParameter('country', $country)
            ->setParameter('status', MissionStatus::InProgress)
            ->getQuery()
            ->getResult();

        if (empty($activeMissions)) {
            // Si aucune mission active, retourner null (pas de niveau de danger)
            return null;
        }

        $highestDangerLevel = DangerLevel::Low;
        
        foreach ($activeMissions as $mission) {
            $missionDangerLevel = $mission->getDanger();
            
            if ($this->isDangerLevelHigher($missionDangerLevel, $highestDangerLevel)) {
                $highestDangerLevel = $missionDangerLevel;
            }
        }

        return $highestDangerLevel;
    }

    /**
     * Compare deux niveaux de danger et retourne true si le premier est plus élevé que le second
     */
    private function isDangerLevelHigher(DangerLevel $level1, DangerLevel $level2): bool
    {
        $dangerLevels = [
            'Low' => 1,
            'Medium' => 2,
            'High' => 3,
            'Critical' => 4
        ];

        return $dangerLevels[$level1->value] > $dangerLevels[$level2->value];
    }

    /**
     * Met à jour le niveau de danger du pays lorsqu'une mission est créée
     */
    public function updateCountryDangerLevelFromMission(Mission $mission): void
    {
        $country = $mission->getCountry();
        
        if ($country) {
            $this->updateCountryDangerLevel($country);
        }
    }
} 