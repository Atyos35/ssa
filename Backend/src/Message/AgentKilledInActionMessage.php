<?php

namespace App\Message;

use App\Domain\Entity\Agent;

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