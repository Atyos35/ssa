<?php

namespace App\Tests\Unit\Application\Bus;

use App\Application\Bus\SimpleQueryBus;
use App\Application\Query\QueryInterface;
use App\Application\Query\GetMissionsQuery;
use App\Application\Handler\Query\GetMissionsHandler;
use App\Application\Handler\Query\GetMissionHandler;
use App\Application\Handler\Query\GetAgentsHandler;
use App\Application\Handler\Query\GetAgentHandler;
use App\Application\Handler\Query\GetCurrentUserHandler;
use PHPUnit\Framework\TestCase;

class SimpleQueryBusTest extends TestCase
{
    private SimpleQueryBus $queryBus;
    private GetMissionsHandler $getMissionsHandler;
    private GetMissionHandler $getMissionHandler;
    private GetAgentsHandler $getAgentsHandler;
    private GetAgentHandler $getAgentHandler;
    private GetCurrentUserHandler $getCurrentUserHandler;

    protected function setUp(): void
    {
        $this->getMissionsHandler = $this->createMock(GetMissionsHandler::class);
        $this->getMissionHandler = $this->createMock(GetMissionHandler::class);
        $this->getAgentsHandler = $this->createMock(GetAgentsHandler::class);
        $this->getAgentHandler = $this->createMock(GetAgentHandler::class);
        $this->getCurrentUserHandler = $this->createMock(GetCurrentUserHandler::class);

        $this->queryBus = new SimpleQueryBus(
            $this->getMissionsHandler,
            $this->getMissionHandler,
            $this->getAgentsHandler,
            $this->getAgentHandler,
            $this->getCurrentUserHandler
        );
    }

    public function testDispatchGetMissionsQuery(): void
    {
        $query = $this->createMock(GetMissionsQuery::class);
        $expectedResult = ['mission1', 'mission2'];
        
        $this->getMissionsHandler
            ->expects($this->once())
            ->method('handle')
            ->with($query)
            ->willReturn($expectedResult);

        $result = $this->queryBus->dispatch($query);
        
        $this->assertEquals($expectedResult, $result);
    }

    public function testDispatchMultipleQueries(): void
    {
        $query1 = $this->createMock(GetMissionsQuery::class);
        $query2 = $this->createMock(GetMissionsQuery::class);
        
        $this->getMissionsHandler
            ->expects($this->exactly(2))
            ->method('handle');

        $this->queryBus->dispatch($query1);
        $this->queryBus->dispatch($query2);
    }

    public function testQueryBusIsQueryBusInterface(): void
    {
        $this->assertInstanceOf(\App\Application\Bus\QueryBusInterface::class, $this->queryBus);
    }
}
