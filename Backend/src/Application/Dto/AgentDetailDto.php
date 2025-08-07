<?php

namespace App\Application\Dto;

use App\Domain\Entity\Agent;

class AgentDetailDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $codeName,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $status,
        public readonly int $yearsOfExperience,
        public readonly \DateTimeImmutable $enrolementDate,
        public readonly ?CountryDto $infiltratedCountry,
        public readonly ?AgentDto $mentor,
        public readonly array $missions,
        public readonly array $messages
    ) {}

    public static function fromEntity(Agent $agent): self
    {
        return new self(
            id: $agent->getId()?->toRfc4122() ?? '',
            codeName: $agent->getCodeName(),
            firstName: $agent->getFirstName(),
            lastName: $agent->getLastName(),
            email: $agent->getEmail(),
            status: $agent->getStatus()->value,
            yearsOfExperience: $agent->getYearsOfExperience(),
            enrolementDate: $agent->getEnrolementDate(),
            infiltratedCountry: $agent->getInfiltratedCountry() ? CountryDto::fromEntity($agent->getInfiltratedCountry()) : null,
            mentor: $agent->getMentor() ? AgentDto::fromEntity($agent->getMentor()) : null,
            missions: array_map(fn($mission) => MissionDto::fromEntity($mission), $agent->getMissions()->toArray()),
            messages: array_map(fn($message) => MessageDto::fromEntity($message), $agent->getMessages()->toArray())
        );
    }
} 