<?php

namespace App\Application\Command;

use App\Domain\Entity\AgentStatus;

class UpdateAgentStatusCommand implements CommandInterface
{
    public function __construct(
        public readonly string $agentId,
        public readonly AgentStatus $status
    ) {}
} 