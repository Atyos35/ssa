<?php

namespace App\Domain\Service;

use App\Domain\Entity\Agent;
use App\Domain\Entity\Mission;

class MissionValidationService
{
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

    public function validateMissionAgents(Mission $mission): void
    {
        foreach ($mission->getAgents() as $agent) {
            $this->validateAgentForMission($agent, $mission);
        }
    }

    public function validateAgentAddition(Agent $agent, Mission $mission): void
    {
        $this->validateAgentForMission($agent, $mission);
    }
}