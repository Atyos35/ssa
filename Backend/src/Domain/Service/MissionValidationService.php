<?php

namespace App\Domain\Service;

use App\Domain\Entity\Agent;
use App\Domain\Entity\Mission;

class MissionValidationService
{
    /**
     * Valide qu'un agent peut participer à une mission
     * Un agent ne peut participer que s'il est infiltré dans le pays de la mission
     */
    public function validateAgentForMission(Agent $agent, Mission $mission): void
    {
        if ($agent->getInfiltratedCountry() !== $mission->getCountry()) {
            throw new \DomainException(
                sprintf(
                    'L\'agent "%s" ne peut pas participer à cette mission car il n\'est pas infiltré dans le pays de la mission.',
                    $agent->getCodeName()
                )
            );
        }
    }

    /**
     * Valide tous les agents d'une mission
     */
    public function validateMissionAgents(Mission $mission): void
    {
        foreach ($mission->getAgents() as $agent) {
            $this->validateAgentForMission($agent, $mission);
        }
    }

    /**
     * Valide qu'un agent peut être ajouté à une mission
     */
    public function validateAgentAddition(Agent $agent, Mission $mission): void
    {
        $this->validateAgentForMission($agent, $mission);
    }
} 
