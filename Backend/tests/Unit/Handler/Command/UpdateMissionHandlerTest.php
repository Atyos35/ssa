<?php

namespace App\Tests\Unit\Handler\Command;

use App\Application\Handler\Command\UpdateMissionHandler;
use App\Application\Command\UpdateMissionCommand;
use App\Application\Command\CommandInterface;
use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionStatus;
use App\Domain\Entity\MissionResult;
use App\Domain\Service\MissionValidationService;
use App\Infrastructure\Persistence\Repository\MissionRepository;
use App\Infrastructure\Persistence\Repository\CountryRepository;
use App\Infrastructure\Persistence\Repository\AgentRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UpdateMissionHandlerTest extends TestCase
{
    private UpdateMissionHandler $handler;
    private EntityManagerInterface $entityManager;
    private MissionValidationService $missionValidationService;
    private MissionRepository $missionRepository;
    private CountryRepository $countryRepository;
    private AgentRepository $agentRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->missionValidationService = $this->createMock(MissionValidationService::class);
        $this->missionRepository = $this->createMock(MissionRepository::class);
        $this->countryRepository = $this->createMock(CountryRepository::class);
        $this->agentRepository = $this->createMock(AgentRepository::class);
        
        $this->handler = new UpdateMissionHandler(
            $this->entityManager,
            $this->missionValidationService,
            $this->missionRepository,
            $this->countryRepository,
            $this->agentRepository
        );
    }

    public function testHandleWithValidCommand(): void
    {
        // Arrange
        $command = new UpdateMissionCommand(
            1,
            null, // name
            null, // description
            null, // objectives
            null, // danger
            MissionStatus::Success, // status
            null, // startDate
            new \DateTimeImmutable() // endDate
        );

        $mission = $this->createMock(Mission::class);
        $mission->method('getStatus')->willReturn(MissionStatus::InProgress);
        $mission->method('getMissionResult')->willReturn(null);
        
        // Simuler l'état de endDate
        $endDate = null;
        $mission->method('getEndDate')->willReturnCallback(function () use (&$endDate) {
            return $endDate;
        });
        $mission->method('setEndDate')->willReturnCallback(function ($date) use (&$endDate, $mission) {
            $endDate = $date;
            return $mission;
        });
        
        $mission->expects($this->once())
            ->method('setStatus')
            ->with(MissionStatus::Success);

        $this->missionRepository->method('find')
            ->with(1)
            ->willReturn($mission);

        $this->missionValidationService->expects($this->once())
            ->method('validateMissionAgents')
            ->with($mission);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($entity) {
                if ($entity instanceof MissionResult) {
                    return;
                }
                if ($entity instanceof Mission) {
                    return;
                }
                $this->fail('Unexpected entity type: ' . get_class($entity));
            });
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->handler->handle($command);

        // Assert - Vérifier que le handler a bien traité la commande
        $this->assertTrue(true);
    }

    public function testHandleWithInvalidCommandType(): void
    {
        // Arrange
        $invalidCommand = $this->createMock(CommandInterface::class);
        
        // Assert & Act
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected UpdateMissionCommand');
        
        $this->handler->handle($invalidCommand);
    }

    public function testHandleWithNonExistentMission(): void
    {
        // Arrange
        $command = new UpdateMissionCommand(
            999,
            null, // name
            null, // description
            null, // objectives
            null, // danger
            MissionStatus::Success, // status
            null, // startDate
            new \DateTimeImmutable() // endDate
        );

        $this->missionRepository->method('find')
            ->with(999)
            ->willReturn(null);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Mission not found');
        
        $this->handler->handle($command);
    }

    public function testHandleWithStatusInProgress(): void
    {
        // Arrange
        $command = new UpdateMissionCommand(
            1,
            null, // name
            null, // description
            null, // objectives
            null, // danger
            MissionStatus::InProgress, // status
            null, // startDate
            null // endDate
        );

        $mission = $this->createMock(Mission::class);
        $mission->method('getStatus')->willReturn(MissionStatus::InProgress);
        $mission->expects($this->once())
            ->method('setStatus')
            ->with(MissionStatus::InProgress);
        $mission->expects($this->never())
            ->method('setEndDate'); // Pas de date de fin pour InProgress

        $this->missionRepository->method('find')
            ->with(1)
            ->willReturn($mission);

        $this->missionValidationService->expects($this->once())
            ->method('validateMissionAgents')
            ->with($mission);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($mission);
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->handler->handle($command);

        // Assert
        $this->assertTrue(true);
    }
}
