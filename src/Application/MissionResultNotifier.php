<?php

namespace App\Application;

use App\Entity\Mission;
use App\Entity\MissionResult;
use App\Entity\MissionStatus;
use Doctrine\ORM\EntityManagerInterface;

class MissionResultNotifier
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Crée un résultat de mission quand une mission se termine (Success ou Failure).
     */
    public function createMissionResult(Mission $mission): void
    {
        $status = $mission->getStatus();
        
        // Vérifier que la mission est terminée
        if ($status !== MissionStatus::Success && $status !== MissionStatus::Failure) {
            return; // Mission pas encore terminée
        }

        // Vérifier qu'il n'y a pas déjà un résultat
        if ($mission->getMissionResult() !== null) {
            return; // Résultat déjà créé
        }

        // Créer le résultat de mission
        $missionResult = new MissionResult();
        $missionResult->setStatus($status);
        $missionResult->setMission($mission);
        
        // Générer un résumé basé sur le statut
        $summary = $this->generateSummary($mission, $status);
        $missionResult->setSummary($summary);

        // Persister le résultat
        $this->em->persist($missionResult);
        $this->em->flush();
    }

    /**
     * Génère un résumé du résultat de la mission.
     */
    private function generateSummary(Mission $mission, MissionStatus $status): string
    {
        $missionName = $mission->getName();
        $countryName = $mission->getCountry()?->getName() ?? 'Pays inconnu';
        $dangerLevel = $mission->getDanger()->value;
        $agentCount = $mission->getAgents()->count();

        if ($status === MissionStatus::Success) {
            return sprintf(
                'Mission "%s" terminée avec succès dans %s. ' .
                'Niveau de danger : %s. %d agent(s) impliqué(s). ' .
                'Objectifs atteints sans perte humaine.',
                $missionName,
                $countryName,
                $dangerLevel,
                $agentCount
            );
        } else {
            return sprintf(
                'Mission "%s" échouée dans %s. ' .
                'Niveau de danger : %s. %d agent(s) impliqué(s). ' .
                'Objectifs non atteints. Enquête en cours.',
                $missionName,
                $countryName,
                $dangerLevel,
                $agentCount
            );
        }
    }
} 