<?php

namespace App\Factory;

use App\Entity\Agent;
use App\Entity\AgentStatus;
use App\Factory\CountryFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Agent>
 */
final class AgentFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Agent::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
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

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Agent $agent): void {})
        ;
    }
}
