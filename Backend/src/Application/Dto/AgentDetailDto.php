<?php

namespace App\Application\Dto;

use App\Domain\Entity\Agent;
use App\Domain\Entity\Mission;
use App\Domain\Entity\Message;

class AgentDetailDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $codeName,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly string $status,
        public readonly int $yearsOfExperience,
        public readonly string $enrolementDate,
        public readonly ?CountryDto $infiltratedCountry,
        public readonly ?AgentDto $mentor,
        public readonly array $missions,
        public readonly array $messages
    ) {}

    public static function fromEntity(Agent $agent): self
    {
        return new self(
            id: $agent->getId() ?? 0,
            codeName: $agent->getCodeName(),
            firstName: $agent->getFirstName(),
            lastName: $agent->getLastName(),
            email: $agent->getEmail(),
            yearsOfExperience: $agent->getYearsOfExperience(),
            status: $agent->getStatus()->value,
            enrolementDate: $agent->getEnrolementDate()->format('Y-m-d'),
            infiltratedCountry: $agent->getInfiltratedCountry() ? CountryDto::fromEntity($agent->getInfiltratedCountry()) : null,
            mentor: $agent->getMentor() ? AgentDto::fromEntity($agent->getMentor()) : null,
            missions: array_map(fn(\App\Domain\Entity\Mission $mission) => MissionDto::fromEntity($mission), $agent->getMissions()->toArray()),
            messages: array_map(fn(\App\Domain\Entity\Message $message) => MessageDto::fromEntity($message), $agent->getMessages()->toArray())
        );
    }
} 