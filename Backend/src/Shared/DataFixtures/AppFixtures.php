<?php

namespace App\Shared\DataFixtures;

use App\Shared\Story\DefaultAgentsStory;
use App\Shared\Story\DefaultCountriesStory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Shared\Story\DefaultMissionsStory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        DefaultCountriesStory::load();
        DefaultAgentsStory::load();
        DefaultMissionsStory::load();
    }
}