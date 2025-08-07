<?php

namespace App\Tests\Functional;

use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class AgentFunctionalTest extends WebTestCase
{
    use ResetDatabase;

    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testGetAgentsList(): void
    {
        // Créer des données de test
        $country = $this->createTestCountry('France', 'FR');
        $agent1 = $this->createTestAgent('Agent001', 'John', 'Doe', AgentStatus::Available, $country);
        $agent2 = $this->createTestAgent('Agent002', 'Jane', 'Smith', AgentStatus::OnMission, $country);

        // Tester GET /api/agents
        $this->client->request('GET', '/api/agents');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData);
        
        // Vérifier que les agents sont retournés
        $this->assertEquals('Agent001', $responseData[0]['codeName']);
        $this->assertEquals('Agent002', $responseData[1]['codeName']);
        
        // Vérifier que firstName et lastName ne sont PAS présents (sécurité)
        $this->assertArrayNotHasKey('firstName', $responseData[0]);
        $this->assertArrayNotHasKey('lastName', $responseData[0]);
        $this->assertArrayNotHasKey('firstName', $responseData[1]);
        $this->assertArrayNotHasKey('lastName', $responseData[1]);
        
        // Vérifier que les champs de sécurité sont présents
        $this->assertArrayHasKey('id', $responseData[0]);
        $this->assertArrayHasKey('codeName', $responseData[0]);
        $this->assertArrayHasKey('status', $responseData[0]);
        $this->assertArrayHasKey('yearsOfExperience', $responseData[0]);
    }

    public function testGetAgentsListWithFilters(): void
    {
        // Créer des données de test
        $country = $this->createTestCountry('France', 'FR');
        $agent1 = $this->createTestAgent('Agent001', 'John', 'Doe', AgentStatus::Available, $country);
        $agent2 = $this->createTestAgent('Agent002', 'Jane', 'Smith', AgentStatus::OnMission, $country);

        // Tester GET /api/agents avec filtre par statut
        $this->client->request('GET', '/api/agents?status=Available');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
        $this->assertCount(1, $responseData);
        $this->assertEquals('Agent001', $responseData[0]['codeName']);
    }

    public function testGetAgentById(): void
    {
        // Créer des données de test
        $country = $this->createTestCountry('Allemagne', 'DE');
        $agent = $this->createTestAgent('Agent003', 'Bob', 'Wilson', AgentStatus::Available, $country);

        // Tester GET /api/agents/{id}
        $this->client->request('GET', '/api/agents/' . $agent->getId());

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals('Agent003', $responseData['codeName']);
        $this->assertEquals('Bob', $responseData['firstName']);
        $this->assertEquals('Wilson', $responseData['lastName']);
        $this->assertEquals('Available', $responseData['status']);
        $this->assertArrayHasKey('infiltratedCountry', $responseData);
        
        // Vérifier que les missions et messages sont présents
        $this->assertArrayHasKey('missions', $responseData);
        $this->assertArrayHasKey('messages', $responseData);
        $this->assertIsArray($responseData['missions']);
        $this->assertIsArray($responseData['messages']);
    }

    public function testGetAgentByIdWithMissionsAndMessages(): void
    {
        // Créer des données de test avec missions et messages
        $country = $this->createTestCountry('France', 'FR');
        $agent = $this->createTestAgent('Agent006', 'David', 'Miller', AgentStatus::Available, $country);
        
        // Créer une mission pour l'agent
        $mission = $this->createTestMission('Mission Test', $country);
        $agent->getMissions()->add($mission);
        $this->entityManager->flush();

        // Tester GET /api/agents/{id}
        $this->client->request('GET', '/api/agents/' . $agent->getId());

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Vérifier que les missions sont présentes mais sans agents (sécurité)
        $this->assertArrayHasKey('missions', $responseData);
        $this->assertCount(1, $responseData['missions']);
        $this->assertEquals('Mission Test', $responseData['missions'][0]['name']);
        $this->assertArrayHasKey('agents', $responseData['missions'][0]);
        $this->assertEmpty($responseData['missions'][0]['agents']); // Pas d'agents pour la sécurité
        
        // Vérifier que les messages sont présents mais sans expéditeur/destinataire (sécurité)
        $this->assertArrayHasKey('messages', $responseData);
        $this->assertIsArray($responseData['messages']);
    }

    public function testCreateAgent(): void
    {
        // Créer un pays pour l'infiltration
        $country = $this->createTestCountry('Espagne', 'ES');

        // Tester POST /api/agents
        $agentData = [
            'codeName' => 'Agent004',
            'firstName' => 'Alice',
            'lastName' => 'Brown',
            'email' => 'agent004@example.com',
            'password' => 'password123',
            'yearsOfExperience' => 8,
            'status' => 'Available',
            'infiltratedCountryId' => $country->getId()
        ];

        $this->client->request(
            'POST',
            '/api/agents',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($agentData)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Agent created successfully', $responseData['message']);

        // Vérifier que l'agent a été créé en base
        $this->entityManager->clear();
        $createdAgent = $this->entityManager->getRepository(Agent::class)->findOneBy(['codeName' => 'Agent004']);
        
        $this->assertNotNull($createdAgent);
        $this->assertEquals('Alice', $createdAgent->getFirstName());
        $this->assertEquals('Brown', $createdAgent->getLastName());
        $this->assertEquals(AgentStatus::Available, $createdAgent->getStatus());
        $this->assertEquals($country->getId(), $createdAgent->getInfiltratedCountry()->getId());
    }

    public function testPatchAgentStatus(): void
    {
        // Créer des données de test
        $country = $this->createTestCountry('Italie', 'IT');
        $agent = $this->createTestAgent('Agent005', 'Charlie', 'Davis', AgentStatus::Available, $country);

        // Tester PATCH /api/agents/{id}/status
        $patchData = [
            'status' => 'On Mission'
        ];

        $this->client->request(
            'PATCH',
            '/api/agents/' . $agent->getId() . '/status',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($patchData)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('On Mission', $responseData['status']);

        // Vérifier que le statut a été mis à jour en base
        $this->entityManager->clear();
        $updatedAgent = $this->entityManager->getRepository(Agent::class)->find($agent->getId());
        
        $this->assertEquals(AgentStatus::OnMission, $updatedAgent->getStatus());
    }

    public function testPatchAgentStatusToKilledInAction(): void
    {
        // Créer des données de test
        $country = $this->createTestCountry('Portugal', 'PT');
        $agent = $this->createTestAgent('Agent006', 'David', 'Miller', AgentStatus::OnMission, $country);

        // Tester PATCH /api/agents/{id}/status vers KilledInAction
        $patchData = [
            'status' => 'Killed in Action'
        ];

        $this->client->request(
            'PATCH',
            '/api/agents/' . $agent->getId() . '/status',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($patchData)
        );

        $this->assertResponseIsSuccessful();

        // Vérifier que le statut a été mis à jour en base
        $this->entityManager->clear();
        $updatedAgent = $this->entityManager->getRepository(Agent::class)->find($agent->getId());
        
        $this->assertEquals(AgentStatus::KilledInAction, $updatedAgent->getStatus());
    }

    public function testGetAgentNotFound(): void
    {
        $this->client->request('GET', '/api/agents/00000000-0000-0000-0000-000000000000');

        $this->assertResponseStatusCodeSame(400); // Le handler lève une exception
    }

    public function testPatchAgentStatusNotFound(): void
    {
        $patchData = [
            'status' => 'Available'
        ];

        $this->client->request(
            'PATCH',
            '/api/agents/00000000-0000-0000-0000-000000000000/status',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($patchData)
        );

        $this->assertResponseStatusCodeSame(400); // Le handler lève une exception DomainException
    }

    public function testCreateAgentWithInvalidCountry(): void
    {
        // Tester POST /api/agents avec un pays inexistant
        $agentData = [
            'codeName' => 'Agent007',
            'firstName' => 'Eve',
            'lastName' => 'Johnson',
            'email' => 'agent007@example.com',
            'password' => 'password123',
            'yearsOfExperience' => 3,
            'status' => 'Available',
            'infiltratedCountryId' => 999999
        ];

        $this->client->request(
            'POST',
            '/api/agents',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($agentData)
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Country not found', $responseData['error']);
    }

    private function createTestCountry(string $name, string $code): Country
    {
        $country = new Country();
        $country->setName($name);
        $country->setDanger(DangerLevel::Medium);
        $country->setNumberOfAgents(0);

        $this->entityManager->persist($country);
        $this->entityManager->flush();
        return $country;
    }

    private function createTestAgent(string $codeName, string $firstName, string $lastName, AgentStatus $status, ?Country $country = null): Agent
    {
        $agent = new Agent();
        $agent->setCodeName($codeName);
        $agent->setFirstName($firstName);
        $agent->setLastName($lastName);
        $agent->setEmail($codeName . '@example.com');
        $agent->setPassword('password123');
        $agent->setStatus($status);
        $agent->setEmailVerified(true);
        $agent->setYearsOfExperience(5);
        $agent->setEnrolementDate(new \DateTimeImmutable());
        $agent->setInfiltratedCountry($country);

        $this->entityManager->persist($agent);
        $this->entityManager->flush();
        return $agent;
    }

    private function createTestMission(string $name, Country $country): Mission
    {
        $mission = new Mission();
        $mission->setName($name);
        $mission->setDescription('Description de test pour ' . $name);
        $mission->setObjectives('Objectifs de test pour ' . $name);
        $mission->setDanger(DangerLevel::Medium);
        $mission->setStatus(MissionStatus::InProgress);
        $mission->setStartDate(new \DateTimeImmutable());
        $mission->setCountry($country);

        $this->entityManager->persist($mission);
        $this->entityManager->flush();
        return $mission;
    }
} 