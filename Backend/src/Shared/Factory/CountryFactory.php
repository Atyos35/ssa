<?php

namespace App\Shared\Factory;

use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class CountryFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Country::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->text(100),
            'numberOfAgents' => self::faker()->randomNumber(),
        ];
    }

    protected function initialize(): static
    {
        return $this
        ;
    }
}