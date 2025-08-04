<?php

namespace App\Dto;

use App\Entity\AgentStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class AgentPatchDto
{
    #[Assert\Choice(choices: [AgentStatus::Available, AgentStatus::OnMission, AgentStatus::Retired, AgentStatus::KilledInAction])]
    public ?AgentStatus $status = null;

    public function getStatus(): ?AgentStatus
    {
        return $this->status;
    }

    public function setStatus(?AgentStatus $status): self
    {
        $this->status = $status;
        return $this;
    }
} 