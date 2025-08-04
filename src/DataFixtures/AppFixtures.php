<?php

namespace App\DataFixtures;

use App\Story\DefaultAgentsStory;
use App\Story\DefaultCountriesStory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        DefaultCountriesStory::load();
        DefaultAgentsStory::load();
    }
}