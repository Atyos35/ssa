<?php

namespace App\Tests\Unit\Handler\Command;

use App\Application\Handler\Command\UpdateAgentStatusHandler;
use App\Application\Command\UpdateAgentStatusCommand;
use App\Application\Command\CommandInterface;
use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Domain\Service\AgentStatusChangeService;
use App\Infrastructure\Persistence\Repository\AgentRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateAgentStatusHandlerTest extends TestCase
{
    private UpdateAgentStatusHandler $handler;
    private EntityManagerInterface $entityManager;
    private AgentStatusChangeService $statusChangeService;
    private AgentRepository $agentRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->statusChangeService = $this->createMock(AgentStatusChangeService::class);
        $this->agentRepository = $this->createMock(AgentRepository::class);
        
        $this->handler = new UpdateAgentStatusHandler(
            $this->entityManager,
            $this->statusChangeService,
            $this->agentRepository
        );
    }

    public function testHandleWithValidCommand(): void
    {
        // Arrange
        $command = new UpdateAgentStatusCommand(1, AgentStatus::OnMission);

        $agent = $this->createMock(Agent::class);
        $agent->method('getStatus')->willReturn(AgentStatus::Available);
        $agent->expects($this->once())
            ->method('setStatus')
            ->with(AgentStatus::OnMission);

        $this->agentRepository->method('find')
            ->with(1)
            ->willReturn($agent);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($agent);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->statusChangeService->expects($this->once())
            ->method('handleStatusChange')
            ->with($agent, AgentStatus::Available);

        // Act
        $this->handler->handle($command);

        // Assert - Si on arrive ici sans exception, c'est un succès
        $this->assertTrue(true);
    }

    public function testHandleWithInvalidCommandType(): void
    {
        // Arrange
        $invalidCommand = $this->createMock(CommandInterface::class);
        
        // Assert & Act
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected UpdateAgentStatusCommand');
        
        $this->handler->handle($invalidCommand);
    }

    public function testHandleWithNonExistentAgent(): void
    {
        // Arrange
        $command = new UpdateAgentStatusCommand(999, AgentStatus::OnMission);

        $this->agentRepository->method('find')
            ->with(999)
            ->willReturn(null);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Agent not found');
        
        $this->handler->handle($command);
    }

    public function testHandleWithSameStatus(): void
    {
        // Arrange
        $command = new UpdateAgentStatusCommand(1, AgentStatus::Available);

        $agent = $this->createMock(Agent::class);
        $agent->method('getStatus')->willReturn(AgentStatus::Available);
        $agent->expects($this->once())
            ->method('setStatus')
            ->with(AgentStatus::Available);

        $this->agentRepository->method('find')
            ->with(1)
            ->willReturn($agent);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($agent);
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->statusChangeService->expects($this->once())
            ->method('handleStatusChange')
            ->with($agent, AgentStatus::Available);

        // Act
        $this->handler->handle($command);

        // Assert - Même statut, mais la mise à jour est quand même effectuée
        $this->assertTrue(true);
    }
}
