<?php

namespace App\Domain\Service;

use App\Domain\Entity\Country;
use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionStatus;
use App\Domain\Entity\DangerLevel;
use Doctrine\ORM\EntityManagerInterface;

final class CountryDangerLevelService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Met à jour le niveau de danger d'un pays basé sur les missions actives
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
    private function getHighestDangerLevelFromActiveMissions(Country $country): DangerLevel
    {
        $activeMissions = $this->entityManager->getRepository(Mission::class)
            ->createQueryBuilder('m')
            ->where('m.country = :country')
            ->andWhere('m.status = :status')
            ->setParameter('country', $country)
            ->setParameter('status', MissionStatus::InProgress)
            ->getQuery()
            ->getResult();

        //si pas de mission, danger à Low
        if (empty($activeMissions)) {
            return DangerLevel::Low;
        }

        $highestLevel = DangerLevel::Low;
        
        foreach ($activeMissions as $mission) {
            $missionDanger = $mission->getDanger();
            if ($this->isDangerLevelHigher($missionDanger, $highestLevel)) {
                $highestLevel = $missionDanger;
            }
        }

        return $highestLevel;
    }

    /**
     * Compare deux niveaux de danger et retourne true si le premier est plus élevé
     */
    private function isDangerLevelHigher(DangerLevel $level1, DangerLevel $level2): bool
    {
        $dangerHierarchy = [
            DangerLevel::Low->value => 1,
            DangerLevel::Medium->value => 2,
            DangerLevel::High->value => 3,
            DangerLevel::Critical->value => 4
        ];

        return $dangerHierarchy[$level1->value] > $dangerHierarchy[$level2->value];
    }

    /**
     * Met à jour le niveau de danger d'un pays après la création d'une mission
     */
    public function updateCountryDangerLevelAfterMissionCreation(Mission $mission): void
    {
        $country = $mission->getCountry();
        if (!$country) {
            return;
        }

        // Recalcule le niveau de danger basé sur toutes les missions actives
        $this->updateCountryDangerLevel($country);
    }

    /**
     * Met à jour le niveau de danger d'un pays après le changement de statut d'une mission
     */
    public function updateCountryDangerLevelAfterMissionStatusChange(Mission $mission): void
    {
        $country = $mission->getCountry();
        if (!$country) {
            return;
        }

        $this->updateCountryDangerLevel($country);
    }
} 
