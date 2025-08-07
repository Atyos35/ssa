<?php

namespace App\Shared\Factory;

use App\Domain\Entity\Mission;
use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\MissionStatus;
use App\Shared\Factory\CountryFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Mission>
 */
final class MissionFactory extends PersistentProxyObjectFactory
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
        return Mission::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->text(100),
            'danger' => self::faker()->randomElement(DangerLevel::cases()),
            'status' => MissionStatus::InProgress,
            'description' => self::faker()->text(500),
            'objectives' => self::faker()->text(500),
            'startDate' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'endDate' => null,
            'country' => CountryFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Mission $mission): void {})
        ;
    }
}
