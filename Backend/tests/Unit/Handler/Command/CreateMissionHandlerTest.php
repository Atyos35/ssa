<?php

namespace App\Tests\Unit\Handler\Command;

use App\Application\Handler\Command\CreateMissionHandler;
use App\Application\Command\CreateMissionCommand;
use App\Application\Command\CommandInterface;
use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionStatus;
use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\Country;
use App\Domain\Entity\Agent;
use App\Domain\Service\MissionCreationService;
use App\Domain\Service\MissionValidationService;
use App\Infrastructure\Persistence\Repository\CountryRepository;
use App\Infrastructure\Persistence\Repository\AgentRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CreateMissionHandlerTest extends TestCase
{
    private CreateMissionHandler $handler;
    private EntityManagerInterface $entityManager;
    private MissionCreationService $missionCreationService;
    private MissionValidationService $missionValidationService;
    private CountryRepository $countryRepository;
    private AgentRepository $agentRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->missionCreationService = $this->createMock(MissionCreationService::class);
        $this->missionValidationService = $this->createMock(MissionValidationService::class);
        $this->countryRepository = $this->createMock(CountryRepository::class);
        $this->agentRepository = $this->createMock(AgentRepository::class);
        
        $this->handler = new CreateMissionHandler(
            $this->entityManager,
            $this->missionCreationService,
            $this->missionValidationService,
            $this->countryRepository,
            $this->agentRepository
        );
    }

    public function testHandleWithValidCommand(): void
    {
        // Arrange
        $command = new CreateMissionCommand(
            'Test Mission',
            'Test Description',
            'Test Objectives',
            DangerLevel::High,
            MissionStatus::InProgress,
            new \DateTimeImmutable(),
            null,
            1,
            [1, 2]
        );

        $country = $this->createMock(Country::class);
        $agent1 = $this->createMock(Agent::class);
        $agent1->method('getInfiltratedCountry')->willReturn($country);
        $agent2 = $this->createMock(Agent::class);
        $agent2->method('getInfiltratedCountry')->willReturn($country);

        $this->countryRepository->method('find')
            ->with(1)
            ->willReturn($country);

        $this->agentRepository->method('find')
            ->willReturnCallback(function ($id) use ($agent1, $agent2) {
                return match ($id) {
                    1 => $agent1,
                    2 => $agent2,
                    default => null
                };
            });

        $this->missionValidationService->expects($this->once())
            ->method('validateMissionAgents')
            ->with($this->isInstanceOf(Mission::class));

        $this->missionCreationService->expects($this->once())
            ->method('handleMissionCreation')
            ->with($this->isInstanceOf(Mission::class));

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
        $this->expectExceptionMessage('Expected CreateMissionCommand');
        
        $this->handler->handle($invalidCommand);
    }

    public function testHandleWithNonExistentCountry(): void
    {
        // Arrange
        $command = new CreateMissionCommand(
            'Test Mission',
            'Test Description',
            'Test Objectives',
            DangerLevel::High,
            MissionStatus::InProgress,
            new \DateTimeImmutable(),
            null,
            999,
            []
        );

        $this->countryRepository->method('find')
            ->with(999)
            ->willReturn(null);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Country not found');
        
        $this->handler->handle($command);
    }

    public function testHandleWithNonExistentAgent(): void
    {
        // Arrange
        $command = new CreateMissionCommand(
            'Test Mission',
            'Test Description',
            'Test Objectives',
            DangerLevel::High,
            MissionStatus::InProgress,
            new \DateTimeImmutable(),
            null,
            1,
            [999]
        );

        $country = $this->createMock(Country::class);

        $this->countryRepository->method('find')
            ->with(1)
            ->willReturn($country);

        $this->agentRepository->method('find')
            ->with(999)
            ->willReturn(null);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Agent with id 999 not found');
        
        $this->handler->handle($command);
    }
}
