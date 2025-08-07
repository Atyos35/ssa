<?php

namespace App\Application\Query;

class GetAgentQuery implements QueryInterface
{
    public function __construct(
        public readonly string $agentId
    ) {}
} 