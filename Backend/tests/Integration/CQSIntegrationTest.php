<?php

namespace App\Tests\Integration;

use App\Application\Bus\CommandBusInterface;
use App\Application\Bus\QueryBusInterface;
use App\Application\Command\CreateAgentCommand;
use App\Application\Command\CreateMissionCommand;
use App\Application\Query\GetAgentsQuery;
use App\Application\Query\GetMissionsQuery;
use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\MissionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class CQSIntegrationTest extends KernelTestCase
{
    use ResetDatabase;

    private CommandBusInterface $commandBus;
    private QueryBusInterface $queryBus;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        
        $this->commandBus = static::getContainer()->get(CommandBusInterface::class);
        $this->queryBus = static::getContainer()->get(QueryBusInterface::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCQSFlowCreateAgentThenQuery(): void
    {
        // Arrange - Créer un pays
        $country = new Country();
        $country->setName('Test Country');
        $country->setDanger(DangerLevel::Low);
        
        $this->entityManager->persist($country);
        $this->entityManager->flush();

        // Act 1 - Créer un agent via Command
        $command = new CreateAgentCommand(
            'Agent001',
            'John',
            'Doe',
            'john.doe@test.com',
            'password123',
            5,
            AgentStatus::Available,
            new \DateTimeImmutable(),
            $country->getId(),
            null
        );

        $this->commandBus->dispatch($command);

        // Act 2 - Récupérer l'agent via Query
        $query = new GetAgentsQuery(null, null, 1, 10);
        $agents = $this->queryBus->dispatch($query);

        // Assert
        $this->assertIsArray($agents);
        $this->assertCount(1, $agents);
        $this->assertEquals('Agent001', $agents[0]->codeName);
        $this->assertEquals('Available', $agents[0]->status);
    }

    public function testCQSFlowCreateMissionThenQuery(): void
    {
        // Arrange - Créer un pays et un agent
        $country = new Country();
        $country->setName('Test Country');
        $country->setDanger(DangerLevel::Low);
        
        $agent = new Agent();
        $agent->setCodeName('Agent001');
        $agent->setFirstName('John');
        $agent->setLastName('Doe');
        $agent->setPassword('password123');
        $agent->setEmail('john.doe@test.com');
        $agent->setYearsOfExperience(5);
        $agent->setStatus(AgentStatus::Available);
        $agent->setEnrolementDate(new \DateTimeImmutable());
        $agent->setInfiltratedCountry($country);
        
        $this->entityManager->persist($country);
        $this->entityManager->persist($agent);
        $this->entityManager->flush();

        // Act 1 - Créer une mission via Command
        $command = new CreateMissionCommand(
            'Test Mission',
            'Test Description',
            'Test Objectives',
            DangerLevel::High,
            MissionStatus::InProgress,
            new \DateTimeImmutable(),
            null, // endDate
            $country->getId(),
            [$agent->getId()]
        );

        $this->commandBus->dispatch($command);

        // Act 2 - Récupérer les missions via Query
        $query = new GetMissionsQuery(null, null, null, 1, 10);
        $missions = $this->queryBus->dispatch($query);

        // Assert
        $this->assertIsArray($missions);
        $this->assertCount(1, $missions);
        $this->assertEquals('Test Mission', $missions[0]->name);
        $this->assertEquals('High', $missions[0]->danger);
    }

    public function testCQSCommandQuerySeparation(): void
    {
        // Arrange
        $country = new Country();
        $country->setName('Test Country');
        $country->setDanger(DangerLevel::Low);
        
        $this->entityManager->persist($country);
        $this->entityManager->flush();

        // Act 1 - Command ne retourne rien
        $command = new CreateAgentCommand(
            'Agent002',
            'Jane',
            'Smith',
            'jane.smith@test.com',
            'password123',
            3,
            AgentStatus::Available,
            new \DateTimeImmutable(),
            $country->getId(),
            null
        );

        $result = $this->commandBus->dispatch($command);

        // Assert 1 - Command retourne void
        $this->assertNull($result);

        // Act 2 - Query retourne des données
        $query = new GetAgentsQuery(null, null, 1, 10);
        $agents = $this->queryBus->dispatch($query);

        // Assert 2 - Query retourne des données
        $this->assertIsArray($agents);
        $this->assertGreaterThan(0, count($agents));
    }
}
