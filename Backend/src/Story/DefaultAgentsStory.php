<?php

namespace App\Story;

use Zenstruck\Foundry\Story;
use App\Factory\AgentFactory;

final class DefaultAgentsStory extends Story
{
    public function build(): void
    {
        AgentFactory::createMany(3);
    }
}
