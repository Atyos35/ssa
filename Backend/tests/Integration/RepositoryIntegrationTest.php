<?php

namespace App\Tests\Integration;

use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use App\Infrastructure\Persistence\Repository\AgentRepository;
use App\Infrastructure\Persistence\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class RepositoryIntegrationTest extends KernelTestCase
{
    use ResetDatabase;

    private EntityManagerInterface $entityManager;
    private AgentRepository $agentRepository;
    private CountryRepository $countryRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->agentRepository = static::getContainer()->get(AgentRepository::class);
        $this->countryRepository = static::getContainer()->get(CountryRepository::class);
    }

    public function testAgentRepositoryFindWithFilters(): void
    {
        // Arrange - Créer des données de test
        $country = new Country();
        $country->setName('Test Country');
        $country->setDanger(DangerLevel::Low);
        
        $agent1 = new Agent();
        $agent1->setCodeName('Agent001');
        $agent1->setFirstName('John');
        $agent1->setLastName('Doe');
        $agent1->setPassword('password123');
        $agent1->setEmail('john.doe@test.com');
        $agent1->setYearsOfExperience(5);
        $agent1->setStatus(AgentStatus::Available);
        $agent1->setEnrolementDate(new \DateTimeImmutable());
        $agent1->setInfiltratedCountry($country);
        
        $agent2 = new Agent();
        $agent2->setCodeName('Agent002');
        $agent2->setFirstName('Jane');
        $agent2->setLastName('Smith');
        $agent2->setPassword('password123');
        $agent2->setEmail('jane.smith@test.com');
        $agent2->setYearsOfExperience(3);
        $agent2->setStatus(AgentStatus::OnMission);
        $agent2->setEnrolementDate(new \DateTimeImmutable());
        $agent2->setInfiltratedCountry($country);
        
        $this->entityManager->persist($country);
        $this->entityManager->persist($agent1);
        $this->entityManager->persist($agent2);
        $this->entityManager->flush();

        // Act - Tester findWithFilters
        $agents = $this->agentRepository->findWithFilters('Available', $country->getId(), 1, 10);
        
        // Assert
        $this->assertIsArray($agents);
        $this->assertCount(1, $agents);
        $this->assertEquals('Agent001', $agents[0]->getCodeName());
        $this->assertEquals(AgentStatus::Available, $agents[0]->getStatus());
    }

    public function testAgentRepositoryFindByCountry(): void
    {
        // Arrange - Créer des données de test
        $country = new Country();
        $country->setName('Test Country 2');
        $country->setDanger(DangerLevel::Medium);
        
        $agent = new Agent();
        $agent->setCodeName('Agent003');
        $agent->setFirstName('Bob');
        $agent->setLastName('Wilson');
        $agent->setPassword('password123');
        $agent->setEmail('bob.wilson@test.com');
        $agent->setYearsOfExperience(7);
        $agent->setStatus(AgentStatus::Available);
        $agent->setEnrolementDate(new \DateTimeImmutable());
        $agent->setInfiltratedCountry($country);
        
        $this->entityManager->persist($country);
        $this->entityManager->persist($agent);
        $this->entityManager->flush();

        // Act - Tester findByCountry
        $agents = $this->agentRepository->findByCountry($country->getId());
        
        // Assert
        $this->assertIsArray($agents);
        $this->assertCount(1, $agents);
        $this->assertEquals('Agent003', $agents[0]->getCodeName());
        $this->assertEquals($country->getId(), $agents[0]->getInfiltratedCountry()->getId());
    }

    public function testAgentRepositoryFindAvailableAgents(): void
    {
        // Arrange - Créer des données de test
        $country = new Country();
        $country->setName('Test Country 3');
        $country->setDanger(DangerLevel::High);
        
        $agent1 = new Agent();
        $agent1->setCodeName('Agent004');
        $agent1->setFirstName('Alice');
        $agent1->setLastName('Johnson');
        $agent1->setPassword('password123');
        $agent1->setEmail('alice.johnson@test.com');
        $agent1->setYearsOfExperience(4);
        $agent1->setStatus(AgentStatus::Available);
        $agent1->setEnrolementDate(new \DateTimeImmutable());
        $agent1->setInfiltratedCountry($country);
        
        $agent2 = new Agent();
        $agent2->setCodeName('Agent005');
        $agent2->setFirstName('Charlie');
        $agent2->setLastName('Brown');
        $agent2->setPassword('password123');
        $agent2->setEmail('charlie.brown@test.com');
        $agent2->setYearsOfExperience(6);
        $agent2->setStatus(AgentStatus::OnMission);
        $agent2->setEnrolementDate(new \DateTimeImmutable());
        $agent2->setInfiltratedCountry($country);
        
        $this->entityManager->persist($country);
        $this->entityManager->persist($agent1);
        $this->entityManager->persist($agent2);
        $this->entityManager->flush();

        // Act - Tester findAvailableAgents
        $agents = $this->agentRepository->findAvailableAgents();
        
        // Assert
        $this->assertIsArray($agents);
        $this->assertGreaterThan(0, count($agents));
        
        // Vérifier que tous les agents retournés sont disponibles
        foreach ($agents as $agent) {
            $this->assertEquals(AgentStatus::Available, $agent->getStatus());
        }
    }
}
