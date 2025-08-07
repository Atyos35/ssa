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

    /**
     * Crée automatiquement un résultat de mission quand le statut change vers Success ou Failure
     */
    public function createMissionResult(Mission $mission): void
    {
        // Vérifier que la mission est terminée (Success ou Failure)
        if (!in_array($mission->getStatus(), [MissionStatus::Success, MissionStatus::Failure])) {
            return;
        }

        // Vérifier qu'il n'y a pas déjà un résultat
        if ($mission->getMissionResult() !== null) {
            return;
        }

        // Créer le résultat de mission
        $missionResult = new MissionResult();
        $missionResult->setStatus($mission->getStatus());
        $missionResult->setMission($mission);
        
        // Générer un résumé automatique basé sur le statut
        $summary = $this->generateSummary($mission);
        $missionResult->setSummary($summary);

        // Persister le résultat
        $this->entityManager->persist($missionResult);
        
        // Mettre à jour la date de fin de la mission si elle n'est pas définie
        if ($mission->getEndDate() === null) {
            $mission->setEndDate(new \DateTimeImmutable());
        }
    }

    /**
     * Génère un résumé automatique pour le résultat de mission
     */
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
