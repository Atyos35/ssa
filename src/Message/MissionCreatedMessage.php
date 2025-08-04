<?php

namespace App\Message;

use App\Entity\Mission;

/**
 * Message envoyé quand une nouvelle mission est créée
 */
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