<?php

namespace App\Application\Dto;

use App\Domain\Entity\Agent;

class AgentDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $codeName,
        public readonly string $status,
        public readonly int $yearsOfExperience,
        public readonly ?CountryDto $infiltratedCountry
    ) {}

    public static function fromEntity(Agent $agent): self
    {
        return new self(
            id: $agent->getId()?->toRfc4122() ?? '',
            codeName: $agent->getCodeName(),
            status: $agent->getStatus()->value,
            yearsOfExperience: $agent->getYearsOfExperience(),
            infiltratedCountry: $agent->getInfiltratedCountry() ? CountryDto::fromEntity($agent->getInfiltratedCountry()) : null
        );
    }
} 