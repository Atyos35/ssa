<?php

namespace App\Shared\Factory;

use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Shared\Factory\CountryFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class AgentFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Agent::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'codeName' => self::faker()->text(50),
            'email' => self::faker()->text(180),
            'emailVerified' => self::faker()->boolean(),
            'enrolementDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'firstName' => self::faker()->text(50),
            'lastName' => self::faker()->text(50),
            'password' => self::faker()->text(255),
            'infiltratedCountry' => CountryFactory::new(),
            'roles' => [],
            'status' => self::faker()->randomElement(AgentStatus::cases()),
            'yearsOfExperience' => self::faker()->randomNumber(),
        ];
    }

    protected function initialize(): static
    {
        return $this
        ;
    }
}