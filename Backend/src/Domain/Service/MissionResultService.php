<?php

namespace App\Domain\Service;

use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionResult;
use App\Domain\Entity\MissionStatus;
use Doctrine\ORM\EntityManagerInterface;

final class MissionResultService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function createMissionResult(Mission $mission): void
    {
        if (!in_array($mission->getStatus(), [MissionStatus::Success, MissionStatus::Failure])) {
            return;
        }

        if ($mission->getMissionResult() !== null) {
            return;
        }

        $missionResult = new MissionResult();
        $missionResult->setStatus($mission->getStatus());
        $missionResult->setMission($mission);
        
        $summary = $this->generateSummary($mission);
        $missionResult->setSummary($summary);

        $this->entityManager->persist($missionResult);
        
        if ($mission->getEndDate() === null) {
            $mission->setEndDate(new \DateTimeImmutable());
        }
    }

    private function generateSummary(Mission $mission): string
    {
        $status = $mission->getStatus();
        $missionName = $mission->getName();
        $countryName = $mission->getCountry()?->getName() ?? 'Pays inconnu';
        $agentsCount = $mission->getAgents()->count();

        if ($status === MissionStatus::Success) {
            return sprintf(
                'Mission "%s" terminée avec succès dans %s. %d agent(s) ont participé à cette mission.',
                $missionName,
                $countryName,
                $agentsCount
            );
        }

        if ($status === MissionStatus::Failure) {
            return sprintf(
                'Mission "%s" a échoué dans %s. %d agent(s) ont participé à cette mission.',
                $missionName,
                $countryName,
                $agentsCount
            );
        }

        return sprintf(
            'Mission "%s" dans %s avec %d agent(s).',
            $missionName,
            $countryName,
            $agentsCount
        );
    }
}