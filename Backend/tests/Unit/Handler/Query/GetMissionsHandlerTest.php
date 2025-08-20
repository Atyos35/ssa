<?php

namespace App\Tests\Unit\Handler\Query;

use App\Application\Handler\Query\GetMissionsHandler;
use App\Application\Query\GetMissionsQuery;
use App\Application\Query\QueryInterface;
use App\Application\Dto\MissionDto;
use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionStatus;
use App\Domain\Entity\DangerLevel;
use App\Infrastructure\Persistence\Repository\MissionRepository;
use PHPUnit\Framework\TestCase;

class GetMissionsHandlerTest extends TestCase
{
    private GetMissionsHandler $handler;
    private MissionRepository $missionRepository;

    protected function setUp(): void
    {
        $this->missionRepository = $this->createMock(MissionRepository::class);
        $this->handler = new GetMissionsHandler($this->missionRepository);
    }

    public function testHandleWithValidQuery(): void
    {
        // Arrange
        $query = new GetMissionsQuery('InProgress', 'High', 1, 1, 10);
        
        $mission = $this->createMock(Mission::class);
        $mission->method('getId')->willReturn(1);
        $mission->method('getName')->willReturn('Test Mission');
        $mission->method('getDescription')->willReturn('Test Description');
        $mission->method('getObjectives')->willReturn('Test Objectives');
        $mission->method('getDanger')->willReturn(DangerLevel::High);
        $mission->method('getStatus')->willReturn(MissionStatus::InProgress);
        $mission->method('getStartDate')->willReturn(new \DateTimeImmutable());
        
        $this->missionRepository->method('findWithFilters')
            ->with('InProgress', 'High', 1, 1, 10)
            ->willReturn([$mission]);
        
        // Act
        $result = $this->handler->handle($query);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(MissionDto::class, $result[0]);
        $this->assertEquals('Test Mission', $result[0]->name);
        $this->assertEquals('High', $result[0]->danger);
    }

    public function testHandleWithInvalidQueryType(): void
    {
        // Arrange
        $invalidQuery = $this->createMock(QueryInterface::class);
        
        // Assert & Act
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected GetMissionsQuery');
        
        $this->handler->handle($invalidQuery);
    }

    public function testHandleWithNullFilters(): void
    {
        // Arrange
        $query = new GetMissionsQuery(null, null, null, 1, 10);
        
        $mission = $this->createMock(Mission::class);
        $mission->method('getId')->willReturn(2);
        $mission->method('getName')->willReturn('Test Mission');
        $mission->method('getDescription')->willReturn('Test Description');
        $mission->method('getObjectives')->willReturn('Test Objectives');
        $mission->method('getDanger')->willReturn(DangerLevel::Medium);
        $mission->method('getStatus')->willReturn(MissionStatus::InProgress);
        $mission->method('getStartDate')->willReturn(new \DateTimeImmutable());
        
        $this->missionRepository->method('findWithFilters')
            ->with(null, null, null, 1, 10)
            ->willReturn([$mission]);
        
        // Act
        $result = $this->handler->handle($query);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }
}
