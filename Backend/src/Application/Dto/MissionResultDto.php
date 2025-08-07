<?php

namespace App\Application\Dto;

use App\Domain\Entity\MissionResult;

class MissionResultDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $status,
        public readonly ?string $summary
    ) {}

    public static function fromEntity(MissionResult $missionResult): self
    {
        return new self(
            id: $missionResult->getId(),
            status: $missionResult->getStatus()->value,
            summary: $missionResult->getSummary()
        );
    }
} 