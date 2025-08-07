<?php

namespace App\Application\Dto;

use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionResult;

class MissionDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly string $objectives,
        public readonly string $danger,
        public readonly string $status,
        public readonly string $startDate,
        public readonly ?string $endDate,
        public readonly ?CountryDto $country,
        public readonly array $agents,
        public readonly ?MissionResultDto $missionResult
    ) {}

    public static function fromEntity(Mission $mission): self
    {
        return new self(
            id: $mission->getId(),
            name: $mission->getName(),
            description: $mission->getDescription(),
            objectives: $mission->getObjectives(),
            danger: $mission->getDanger()->value,
            status: $mission->getStatus()->value,
            startDate: $mission->getStartDate()->format('Y-m-d'),
            endDate: $mission->getEndDate()?->format('Y-m-d'),
            country: $mission->getCountry() ? CountryDto::fromEntity($mission->getCountry()) : null,
            agents: [], // On ne retourne pas les agents pour des raisons de sécurité
            missionResult: $mission->getMissionResult() ? MissionResultDto::fromEntity($mission->getMissionResult()) : null
        );
    }
} 