<?php

namespace App\Application\Command;

use App\Domain\Entity\AgentStatus;

class CreateAgentCommand implements CommandInterface
{
    public function __construct(
        public readonly string $codeName,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $password,
        public readonly int $yearsOfExperience,
        public readonly AgentStatus $status,
        public readonly \DateTimeImmutable $enrolementDate,
        public readonly ?int $infiltratedCountryId = null,
        public readonly ?string $mentorId = null
    ) {}
} 