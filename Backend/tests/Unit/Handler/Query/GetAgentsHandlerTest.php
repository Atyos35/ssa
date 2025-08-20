<?php

namespace App\Tests\Unit\Handler\Query;

use App\Application\Handler\Query\GetAgentsHandler;
use App\Application\Query\GetAgentsQuery;
use App\Application\Query\QueryInterface;
use App\Application\Dto\AgentDto;
use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Infrastructure\Persistence\Repository\AgentRepository;
use PHPUnit\Framework\TestCase;

class GetAgentsHandlerTest extends TestCase
{
    private GetAgentsHandler $handler;
    private AgentRepository $agentRepository;

    protected function setUp(): void
    {
        $this->agentRepository = $this->createMock(AgentRepository::class);
        $this->handler = new GetAgentsHandler($this->agentRepository);
    }

    public function testHandleWithValidQuery(): void
    {
        // Arrange
        $query = new GetAgentsQuery('Available', 1, 1, 10);
        
        $agent = new Agent();
        $agent->setCodeName('Agent001');
        $agent->setFirstName('John');
        $agent->setLastName('Doe');
        $agent->setPassword('password123');
        $agent->setEmail('john.doe@test.com');
        $agent->setYearsOfExperience(5);
        $agent->setStatus(AgentStatus::Available);
        $agent->setEnrolementDate(new \DateTimeImmutable());
        
        $this->agentRepository->method('findWithFilters')
            ->with('Available', 1, 1, 10)
            ->willReturn([$agent]);
        
        // Act
        $result = $this->handler->handle($query);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(AgentDto::class, $result[0]);
    }

    public function testHandleWithInvalidQueryType(): void
    {
        // Arrange
        $invalidQuery = $this->createMock(QueryInterface::class);
        
        // Assert & Act
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected GetAgentsQuery');
        
        $this->handler->handle($invalidQuery);
    }

    public function testHandleWithNullFilters(): void
    {
        // Arrange
        $query = new GetAgentsQuery(null, null, 1, 10);
        
        $agent = new Agent();
        $agent->setCodeName('Agent001');
        $agent->setFirstName('John');
        $agent->setLastName('Doe');
        $agent->setPassword('password123');
        $agent->setEmail('john.doe@test.com');
        $agent->setYearsOfExperience(5);
        $agent->setStatus(AgentStatus::Available);
        $agent->setEnrolementDate(new \DateTimeImmutable());
        
        $this->agentRepository->method('findWithFilters')
            ->with(null, null, 1, 10)
            ->willReturn([$agent]);
        
        // Act
        $result = $this->handler->handle($query);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }
}
