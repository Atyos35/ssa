<?php

namespace App\Application\Command;

use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\MissionStatus;

class CreateMissionCommand implements CommandInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $objectives,
        public readonly DangerLevel $danger,
        public readonly MissionStatus $status,
        public readonly \DateTimeImmutable $startDate,
        public readonly ?\DateTimeImmutable $endDate,
        public readonly int $countryId,
        public readonly array $agentIds = []
    ) {}
} 