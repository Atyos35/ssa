<?php

namespace App\Shared\Story;

use Zenstruck\Foundry\Story;
use App\Shared\Factory\MissionFactory;

final class DefaultMissionsStory extends Story
{
    public function build(): void
    {
        MissionFactory::createMany(3);
    }
}
