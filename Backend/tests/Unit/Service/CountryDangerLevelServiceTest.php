<?php

namespace App\Tests\Unit\Service;

use App\Domain\Service\CountryDangerLevelService;
use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionStatus;
use App\Infrastructure\Persistence\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CountryDangerLevelServiceTest extends TestCase
{
    private CountryDangerLevelService $service;
    private EntityManagerInterface $entityManager;
    private MissionRepository $missionRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->missionRepository = $this->createMock(MissionRepository::class);
        
        $this->service = new CountryDangerLevelService(
            $this->entityManager,
            $this->missionRepository
        );
    }

    public function testUpdateCountryDangerLevelWithNoActiveMissions(): void
    {
        // Arrange
        $country = $this->createMock(Country::class);
        $country->method('getId')->willReturn(1);
        $country->method('getDanger')->willReturn(DangerLevel::High);
        $country->expects($this->once())
            ->method('setDanger')
            ->with(DangerLevel::Low);

        $this->missionRepository->method('findByCountry')
            ->with(1)
            ->willReturn([]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($country);
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->service->updateCountryDangerLevel($country);

        // Assert - Vérifié via les mocks
        $this->assertTrue(true);
    }

    public function testUpdateCountryDangerLevelWithLowDangerMissions(): void
    {
        // Arrange
        $country = $this->createMock(Country::class);
        $country->method('getId')->willReturn(1);
        $country->method('getDanger')->willReturn(DangerLevel::High);
        $country->expects($this->once())
            ->method('setDanger')
            ->with(DangerLevel::Low);

        $mission1 = $this->createMock(Mission::class);
        $mission1->method('getStatus')->willReturn(MissionStatus::InProgress);
        $mission1->method('getDanger')->willReturn(DangerLevel::Low);

        $mission2 = $this->createMock(Mission::class);
        $mission2->method('getStatus')->willReturn(MissionStatus::InProgress);
        $mission2->method('getDanger')->willReturn(DangerLevel::Low);

        $this->missionRepository->method('findByCountry')
            ->with(1)
            ->willReturn([$mission1, $mission2]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($country);
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->service->updateCountryDangerLevel($country);

        // Assert - Vérifié via les mocks
        $this->assertTrue(true);
    }

    public function testUpdateCountryDangerLevelWithHighDangerMissions(): void
    {
        // Arrange
        $country = $this->createMock(Country::class);
        $country->method('getId')->willReturn(1);
        $country->method('getDanger')->willReturn(DangerLevel::Low);
        $country->expects($this->once())
            ->method('setDanger')
            ->with(DangerLevel::High);

        $mission1 = $this->createMock(Mission::class);
        $mission1->method('getStatus')->willReturn(MissionStatus::InProgress);
        $mission1->method('getDanger')->willReturn(DangerLevel::Medium);

        $mission2 = $this->createMock(Mission::class);
        $mission2->method('getStatus')->willReturn(MissionStatus::InProgress);
        $mission2->method('getDanger')->willReturn(DangerLevel::High);

        $this->missionRepository->method('findByCountry')
            ->with(1)
            ->willReturn([$mission1, $mission2]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($country);
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->service->updateCountryDangerLevel($country);

        // Assert - Vérifié via les mocks
        $this->assertTrue(true);
    }

    public function testUpdateCountryDangerLevelWithMixedStatusMissions(): void
    {
        // Arrange
        $country = $this->createMock(Country::class);
        $country->method('getId')->willReturn(1);
        $country->method('getDanger')->willReturn(DangerLevel::Low);
        $country->expects($this->once())
            ->method('setDanger')
            ->with(DangerLevel::High);

        $mission1 = $this->createMock(Mission::class);
        $mission1->method('getStatus')->willReturn(MissionStatus::InProgress);
        $mission1->method('getDanger')->willReturn(DangerLevel::High);

        $mission2 = $this->createMock(Mission::class);
        $mission2->method('getStatus')->willReturn(MissionStatus::Success); // Mission terminée
        $mission2->method('getDanger')->willReturn(DangerLevel::High);

        $this->missionRepository->method('findByCountry')
            ->with(1)
            ->willReturn([$mission1, $mission2]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($country);
        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->service->updateCountryDangerLevel($country);

        // Assert - Seule la mission InProgress compte, vérifié via les mocks
        $this->assertTrue(true);
    }
}
