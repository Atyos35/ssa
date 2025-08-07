<?php

namespace App\Application\Bus;

use App\Application\Query\QueryInterface;
use App\Application\Query\GetMissionsQuery;
use App\Application\Query\GetMissionQuery;
use App\Application\Query\GetAgentsQuery;
use App\Application\Query\GetAgentQuery;
use App\Application\Query\GetCurrentUserQuery;
use App\Application\Handler\Query\GetMissionsHandler;
use App\Application\Handler\Query\GetMissionHandler;
use App\Application\Handler\Query\GetAgentsHandler;
use App\Application\Handler\Query\GetAgentHandler;
use App\Application\Handler\Query\GetCurrentUserHandler;

class SimpleQueryBus implements QueryBusInterface
{
    public function __construct(
        private readonly GetMissionsHandler $getMissionsHandler,
        private readonly GetMissionHandler $getMissionHandler,
        private readonly GetAgentsHandler $getAgentsHandler,
        private readonly GetAgentHandler $getAgentHandler,
        private readonly GetCurrentUserHandler $getCurrentUserHandler
    ) {}

    public function dispatch(QueryInterface $query): mixed
    {
        return match (true) {
            $query instanceof GetMissionsQuery => $this->getMissionsHandler->handle($query),
            $query instanceof GetMissionQuery => $this->getMissionHandler->handle($query),
            $query instanceof GetAgentsQuery => $this->getAgentsHandler->handle($query),
            $query instanceof GetAgentQuery => $this->getAgentHandler->handle($query),
            $query instanceof GetCurrentUserQuery => $this->getCurrentUserHandler->handle($query),
            default => throw new \InvalidArgumentException('Unknown query: ' . get_class($query))
        };
    }
} 