<?php

namespace App\Dto;

use App\Entity\MissionStatus;
use App\Entity\DangerLevel;
use Symfony\Component\Validator\Constraints as Assert;

final class MissionPatchDto
{
    #[Assert\Choice(choices: [MissionStatus::InProgress, MissionStatus::Success, MissionStatus::Failure])]
    public ?MissionStatus $status = null;

    #[Assert\Choice(choices: [DangerLevel::Low, DangerLevel::Medium, DangerLevel::High, DangerLevel::Critical])]
    public ?DangerLevel $danger = null;

    public function getStatus(): ?MissionStatus
    {
        return $this->status;
    }

    public function setStatus(?MissionStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDanger(): ?DangerLevel
    {
        return $this->danger;
    }

    public function setDanger(?DangerLevel $danger): self
    {
        $this->danger = $danger;
        return $this;
    }
} 