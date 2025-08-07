<?php

namespace App\Application\Dto;

use App\Domain\Entity\Country;

class CountryDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $dangerLevel,
        public readonly int $numberOfAgents
    ) {}

    public static function fromEntity(Country $country): self
    {
        return new self(
            id: $country->getId(),
            name: $country->getName(),
            dangerLevel: $country->getDanger()?->value ?? 'Unknown',
            numberOfAgents: $country->getNumberOfAgents()
        );
    }
} 