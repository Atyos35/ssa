<?php

namespace App\Tests\Integration\Repository;

use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionStatus;
use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\Country;
use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Infrastructure\Persistence\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class MissionRepositoryTest extends KernelTestCase
{
    use ResetDatabase;

    private EntityManagerInterface $entityManager;
    private MissionRepository $missionRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->missionRepository = static::getContainer()->get(MissionRepository::class);
    }

    public function testFindWithFilters(): void
    {
        // Arrange - Créer des données de test
        $country = new Country();
        $country->setName('Test Country');
        $country->setDanger(DangerLevel::Medium);
        
        $mission1 = new Mission();
        $mission1->setName('Mission Alpha');
        $mission1->setDescription('Description Alpha');
        $mission1->setObjectives('Objectives Alpha');
        $mission1->setDanger(DangerLevel::High);
        $mission1->setStatus(MissionStatus::InProgress);
        $mission1->setStartDate(new \DateTimeImmutable());
        $mission1->setCountry($country);
        
        $mission2 = new Mission();
        $mission2->setName('Mission Beta');
        $mission2->setDescription('Description Beta');
        $mission2->setObjectives('Objectives Beta');
        $mission2->setDanger(DangerLevel::Low);
        $mission2->setStatus(MissionStatus::Success);
        $mission2->setStartDate(new \DateTimeImmutable());
        $mission2->setCountry($country);
        
        $this->entityManager->persist($country);
        $this->entityManager->persist($mission1);
        $this->entityManager->persist($mission2);
        $this->entityManager->flush();

        // Act - Tester findWithFilters avec statut
        $missions = $this->missionRepository->findWithFilters('InProgress', null, $country->getId(), 1, 10);
        
        // Assert
        $this->assertIsArray($missions);
        $this->assertCount(1, $missions);
        $this->assertEquals('Mission Alpha', $missions[0]->getName());
        $this->assertEquals(MissionStatus::InProgress, $missions[0]->getStatus());
    }

    public function testFindByCountry(): void
    {
        // Arrange
        $country1 = new Country();
        $country1->setName('Country 1');
        $country1->setDanger(DangerLevel::Low);
        
        $country2 = new Country();
        $country2->setName('Country 2');
        $country2->setDanger(DangerLevel::High);
        
        $mission1 = new Mission();
        $mission1->setName('Mission in Country 1');
        $mission1->setDescription('Description');
        $mission1->setObjectives('Objectives');
        $mission1->setDanger(DangerLevel::Medium);
        $mission1->setStatus(MissionStatus::InProgress);
        $mission1->setStartDate(new \DateTimeImmutable());
        $mission1->setCountry($country1);
        
        $mission2 = new Mission();
        $mission2->setName('Mission in Country 2');
        $mission2->setDescription('Description');
        $mission2->setObjectives('Objectives');
        $mission2->setDanger(DangerLevel::High);
        $mission2->setStatus(MissionStatus::InProgress);
        $mission2->setStartDate(new \DateTimeImmutable());
        $mission2->setCountry($country2);
        
        $this->entityManager->persist($country1);
        $this->entityManager->persist($country2);
        $this->entityManager->persist($mission1);
        $this->entityManager->persist($mission2);
        $this->entityManager->flush();

        // Act
        $missions = $this->missionRepository->findByCountry($country1->getId());
        
        // Assert
        $this->assertIsArray($missions);
        $this->assertCount(1, $missions);
        $this->assertEquals('Mission in Country 1', $missions[0]->getName());
        $this->assertEquals($country1->getId(), $missions[0]->getCountry()->getId());
    }

    public function testFindInProgressMissions(): void
    {
        // Arrange
        $country = new Country();
        $country->setName('Test Country');
        $country->setDanger(DangerLevel::Medium);
        
        $mission1 = new Mission();
        $mission1->setName('Active Mission');
        $mission1->setDescription('Description');
        $mission1->setObjectives('Objectives');
        $mission1->setDanger(DangerLevel::High);
        $mission1->setStatus(MissionStatus::InProgress);
        $mission1->setStartDate(new \DateTimeImmutable());
        $mission1->setCountry($country);
        
        $mission2 = new Mission();
        $mission2->setName('Completed Mission');
        $mission2->setDescription('Description');
        $mission2->setObjectives('Objectives');
        $mission2->setDanger(DangerLevel::Low);
        $mission2->setStatus(MissionStatus::Success);
        $mission2->setStartDate(new \DateTimeImmutable());
        $mission2->setCountry($country);
        
        $this->entityManager->persist($country);
        $this->entityManager->persist($mission1);
        $this->entityManager->persist($mission2);
        $this->entityManager->flush();

        // Act
        $missions = $this->missionRepository->findInProgressMissions();
        
        // Assert
        $this->assertIsArray($missions);
        $this->assertGreaterThan(0, count($missions));
        
        // Vérifier que toutes les missions retournées sont en cours
        foreach ($missions as $mission) {
            $this->assertEquals(MissionStatus::InProgress, $mission->getStatus());
        }
    }

    public function testFindByDangerLevel(): void
    {
        // Arrange
        $country = new Country();
        $country->setName('Test Country');
        $country->setDanger(DangerLevel::Medium);
        
        $mission1 = new Mission();
        $mission1->setName('High Danger Mission');
        $mission1->setDescription('Description');
        $mission1->setObjectives('Objectives');
        $mission1->setDanger(DangerLevel::High);
        $mission1->setStatus(MissionStatus::InProgress);
        $mission1->setStartDate(new \DateTimeImmutable());
        $mission1->setCountry($country);
        
        $mission2 = new Mission();
        $mission2->setName('Low Danger Mission');
        $mission2->setDescription('Description');
        $mission2->setObjectives('Objectives');
        $mission2->setDanger(DangerLevel::Low);
        $mission2->setStatus(MissionStatus::InProgress);
        $mission2->setStartDate(new \DateTimeImmutable());
        $mission2->setCountry($country);
        
        $this->entityManager->persist($country);
        $this->entityManager->persist($mission1);
        $this->entityManager->persist($mission2);
        $this->entityManager->flush();

        // Act
        $missions = $this->missionRepository->findByDangerLevel('High');
        
        // Assert
        $this->assertIsArray($missions);
        $this->assertGreaterThan(0, count($missions));
        
        // Vérifier que toutes les missions retournées ont le bon niveau de danger
        foreach ($missions as $mission) {
            $this->assertEquals(DangerLevel::High, $mission->getDanger());
        }
    }
}
