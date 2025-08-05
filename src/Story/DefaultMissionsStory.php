<?php

namespace App\Story;

use Zenstruck\Foundry\Story;
use App\Factory\MissionFactory;

final class DefaultMissionsStory extends Story
{
    public function build(): void
    {
        MissionFactory::createMany(3);
    }
}
