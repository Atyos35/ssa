<?php

namespace App\Application\Command;

use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\MissionStatus;

class UpdateMissionCommand implements CommandInterface
{
    public function __construct(
        public readonly int $missionId,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly ?string $objectives = null,
        public readonly ?DangerLevel $danger = null,
        public readonly ?MissionStatus $status = null,
        public readonly ?\DateTimeImmutable $startDate = null,
        public readonly ?\DateTimeImmutable $endDate = null,
        public readonly ?int $countryId = null,
        public readonly ?array $agentIds = null,
        public readonly ?string $missionResultSummary = null
    ) {}
} 