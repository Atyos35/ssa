<?php

namespace App\Message;

use App\Domain\Entity\Mission;

final class MissionCreatedMessage
{
    public function __construct(
        private readonly Mission $mission
    ) {
    }

    public function getMission(): Mission
    {
        return $this->mission;
    }
}