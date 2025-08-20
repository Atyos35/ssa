<?php

namespace App\Tests\Unit\Handler\Query;

use App\Application\Handler\Query\GetAgentHandler;
use App\Application\Query\GetAgentQuery;
use App\Application\Query\QueryInterface;
use App\Application\Dto\AgentDetailDto;
use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Infrastructure\Persistence\Repository\AgentRepository;
use PHPUnit\Framework\TestCase;

class GetAgentHandlerTest extends TestCase
{
    private GetAgentHandler $handler;
    private AgentRepository $agentRepository;

    protected function setUp(): void
    {
        $this->agentRepository = $this->createMock(AgentRepository::class);
        $this->handler = new GetAgentHandler($this->agentRepository);
    }

    public function testHandleWithValidQuery(): void
    {
        // Arrange
        $query = new GetAgentQuery(1);
        
        $agent = new Agent();
        $agent->setCodeName('Agent001');
        $agent->setFirstName('John');
        $agent->setLastName('Doe');
        $agent->setPassword('password123');
        $agent->setEmail('john.doe@test.com');
        $agent->setYearsOfExperience(5);
        $agent->setStatus(AgentStatus::Available);
        $agent->setEnrolementDate(new \DateTimeImmutable());
        
        $this->agentRepository->method('findWithDetails')
            ->with(1)
            ->willReturn($agent);
        
        // Act
        $result = $this->handler->handle($query);
        
        // Assert
        $this->assertInstanceOf(AgentDetailDto::class, $result);
        $this->assertEquals('Agent001', $result->codeName);
        $this->assertEquals('Available', $result->status);
    }

    public function testHandleWithInvalidQueryType(): void
    {
        // Arrange
        $invalidQuery = $this->createMock(QueryInterface::class);
        
        // Assert & Act
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected GetAgentQuery');
        
        $this->handler->handle($invalidQuery);
    }

    public function testHandleWithNonExistentAgent(): void
    {
        // Arrange
        $query = new GetAgentQuery(999);
        
        $this->agentRepository->method('findWithDetails')
            ->with(999)
            ->willReturn(null);
        
        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Agent not found');
        
        $this->handler->handle($query);
    }
}
