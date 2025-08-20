<?php

namespace App\Tests\Integration\Repository;

use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Infrastructure\Persistence\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class CountryRepositoryTest extends KernelTestCase
{
    use ResetDatabase;

    private EntityManagerInterface $entityManager;
    private CountryRepository $countryRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->countryRepository = static::getContainer()->get(CountryRepository::class);
    }

    protected function tearDown(): void
    {
        // Nettoyer l'EntityManager entre les tests
        if ($this->entityManager->isOpen()) {
            $this->entityManager->clear();
        }
        
        parent::tearDown();
    }

    public function testFindAllWithAgents(): void
    {
        // Arrange
        $country1 = new Country();
        $country1->setName('Country with Agents');
        $country1->setDanger(DangerLevel::Medium);
        
        $country2 = new Country();
        $country2->setName('Country without Agents');
        $country2->setDanger(DangerLevel::Low);
        
        $agent = new Agent();
        $agent->setCodeName('Agent001');
        $agent->setFirstName('John');
        $agent->setLastName('Doe');
        $agent->setPassword('password123');
        $agent->setEmail('john.doe@test.com');
        $agent->setYearsOfExperience(5);
        $agent->setStatus(AgentStatus::Available);
        $agent->setEnrolementDate(new \DateTimeImmutable());
        $agent->setInfiltratedCountry($country1);
        
        $this->entityManager->persist($country1);
        $this->entityManager->persist($country2);
        $this->entityManager->persist($agent);
        $this->entityManager->flush();

        // Act
        $result = $this->countryRepository->findAllWithAgents();
        
        // Assert
        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));
        
        // Vérifier qu'on trouve au moins le pays avec agents
        $foundCountryWithAgents = false;
        foreach ($result as $item) {
            if ($item instanceof Country && $item->getName() === 'Country with Agents') {
                $foundCountryWithAgents = true;
                break;
            }
        }
        $this->assertTrue($foundCountryWithAgents);
        
        // Vérifier que tous les éléments sont des entités Country
        foreach ($result as $item) {
            $this->assertInstanceOf(Country::class, $item);
        }
    }

    public function testFindByDangerLevel(): void
    {
        // Arrange
        $highDangerCountry = new Country();
        $highDangerCountry->setName('High Danger Country');
        $highDangerCountry->setDanger(DangerLevel::High);
        
        $lowDangerCountry = new Country();
        $lowDangerCountry->setName('Low Danger Country');
        $lowDangerCountry->setDanger(DangerLevel::Low);
        
        $this->entityManager->persist($highDangerCountry);
        $this->entityManager->persist($lowDangerCountry);
        $this->entityManager->flush();

        // Act
        $countries = $this->countryRepository->findByDangerLevel('High');
        
        // Assert
        $this->assertIsArray($countries);
        $this->assertGreaterThan(0, count($countries));
        
        // Vérifier que tous les pays retournés ont le bon niveau de danger
        foreach ($countries as $country) {
            $this->assertEquals(DangerLevel::High, $country->getDanger());
        }
    }

    public function testFindWithInfiltratedAgents(): void
    {
        // Arrange
        $countryWithAgents = new Country();
        $countryWithAgents->setName('Country with Infiltrated Agents');
        $countryWithAgents->setDanger(DangerLevel::High);
        
        $countryWithoutAgents = new Country();
        $countryWithoutAgents->setName('Country without Infiltrated Agents');
        $countryWithoutAgents->setDanger(DangerLevel::Low);
        
        $agent = new Agent();
        $agent->setCodeName('Agent002');
        $agent->setFirstName('Jane');
        $agent->setLastName('Smith');
        $agent->setPassword('password123');
        $agent->setEmail('jane.smith@test.com');
        $agent->setYearsOfExperience(3);
        $agent->setStatus(AgentStatus::Available);
        $agent->setEnrolementDate(new \DateTimeImmutable());
        $agent->setInfiltratedCountry($countryWithAgents);
        
        $this->entityManager->persist($countryWithAgents);
        $this->entityManager->persist($countryWithoutAgents);
        $this->entityManager->persist($agent);
        $this->entityManager->flush();

        // Act
        $countries = $this->countryRepository->findWithInfiltratedAgents();
        
        // Assert
        $this->assertIsArray($countries);
        $this->assertGreaterThan(0, count($countries));
        
        // Vérifier que le pays avec agents est dans la liste
        $foundCountryWithAgents = false;
        foreach ($countries as $country) {
            if ($country->getName() === 'Country with Infiltrated Agents') {
                $foundCountryWithAgents = true;
                break;
            }
        }
        $this->assertTrue($foundCountryWithAgents, 'Le pays avec agents infiltrés devrait être trouvé');
        
        // Vérifier que le pays sans agents n'est PAS dans la liste
        $foundCountryWithoutAgents = false;
        foreach ($countries as $country) {
            if ($country->getName() === 'Country without Infiltrated Agents') {
                $foundCountryWithoutAgents = true;
                break;
            }
        }
        $this->assertFalse($foundCountryWithoutAgents, 'Le pays sans agents infiltrés ne devrait PAS être trouvé');
    }

    public function testFindWithoutInfiltratedAgents(): void
    {
        // Arrange
        $countryWithoutAgents = new Country();
        $countryWithoutAgents->setName('Isolated Country');
        $countryWithoutAgents->setDanger(DangerLevel::Low);
        
        $this->entityManager->persist($countryWithoutAgents);
        $this->entityManager->flush();

        // Act
        $countries = $this->countryRepository->findWithoutInfiltratedAgents();
        
        // Assert
        $this->assertIsArray($countries);
        
        // Vérifier qu'on trouve au moins le pays sans agents
        $foundIsolatedCountry = false;
        foreach ($countries as $country) {
            if ($country->getName() === 'Isolated Country') {
                $foundIsolatedCountry = true;
                $this->assertEquals(0, $country->getAgents()->count());
            }
        }
        $this->assertTrue($foundIsolatedCountry);
    }

    public function testFindByAgentCount(): void
    {
        // Arrange
        $country = new Country();
        $country->setName('Country with Multiple Agents');
        $country->setDanger(DangerLevel::Medium);
        
        $agent1 = new Agent();
        $agent1->setCodeName('Agent003');
        $agent1->setFirstName('Bob');
        $agent1->setLastName('Wilson');
        $agent1->setPassword('password123');
        $agent1->setEmail('bob.wilson@test.com');
        $agent1->setYearsOfExperience(7);
        $agent1->setStatus(AgentStatus::Available);
        $agent1->setEnrolementDate(new \DateTimeImmutable());
        $agent1->setInfiltratedCountry($country);
        
        $agent2 = new Agent();
        $agent2->setCodeName('Agent004');
        $agent2->setFirstName('Alice');
        $agent2->setLastName('Johnson');
        $agent2->setPassword('password123');
        $agent2->setEmail('alice.johnson@test.com');
        $agent2->setYearsOfExperience(4);
        $agent2->setStatus(AgentStatus::OnMission);
        $agent2->setEnrolementDate(new \DateTimeImmutable());
        $agent2->setInfiltratedCountry($country);
        
        // Ajouter les agents à la collection du pays
        $country->getAgents()->add($agent1);
        $country->getAgents()->add($agent2);
        
        $this->entityManager->persist($country);
        $this->entityManager->persist($agent1);
        $this->entityManager->persist($agent2);
        $this->entityManager->flush();

        // Act
        $countries = $this->countryRepository->findByAgentCount(2);
        
        // Assert
        $this->assertIsArray($countries);
        
        // Vérifier qu'on trouve le pays avec exactement 2 agents
        $foundCountryWithTwoAgents = false;
        foreach ($countries as $country) {
            if ($country->getName() === 'Country with Multiple Agents') {
                $foundCountryWithTwoAgents = true;
                $this->assertEquals(2, $country->getAgents()->count());
            }
        }
        $this->assertTrue($foundCountryWithTwoAgents);
    }
}
