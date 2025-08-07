<?php

namespace App\Shared\Story;

use Zenstruck\Foundry\Story;
use App\Shared\Factory\AgentFactory;

final class DefaultAgentsStory extends Story
{
    public function build(): void
    {
        AgentFactory::createMany(3);
    }
}
