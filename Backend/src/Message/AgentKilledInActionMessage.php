<?php

namespace App\Message;

use App\Domain\Entity\Agent;

/**
 * Message envoyé quand un agent passe au statut "Killed in Action"
 */
final class AgentKilledInActionMessage
{
    public function __construct(
        private readonly Agent $killedAgent
    ) {
    }

    public function getKilledAgent(): Agent
    {
        return $this->killedAgent;
    }
} 