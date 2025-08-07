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

class MissionValidationFunctionalTest extends WebTestCase
{
    use ResetDatabase;

    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testPostMissionWithValidAgentSucceeds(): void
    {
        // Créer un pays
        $country = $this->createTestCountry('France');

        // Créer un agent infiltré dans ce pays
        $agent = $this->createTestAgent('Agent001', 'John', 'Doe', AgentStatus::Available, $country);

        $this->entityManager->flush();

        // Créer une mission avec l'agent
        $missionData = [
            'name' => 'Mission Test',
            'danger' => 'High',
            'status' => 'InProgress',
            'description' => 'Description de la mission test',
            'objectives' => 'Objectifs de la mission test',
            'startDate' => '2024-01-15',
            'country' => '/api/countries/' . $country->getId(),
            'agents' => [
                '/api/agents/' . $agent->getId()
            ]
        ];

        $this->client->request(
            'POST',
            '/api/missions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($missionData)
        );

        // Vérifier que la requête réussit
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Mission created successfully', $responseData['message']);
    }

    public function testPostMissionWithInvalidAgentReturnsError(): void
    {
        // Créer deux pays différents
        $country1 = $this->createTestCountry('France');
        $country2 = $this->createTestCountry('Allemagne');

        // Créer un agent infiltré dans le pays 1
        $agent = $this->createTestAgent('Agent001', 'John', 'Doe', AgentStatus::Available, $country1);

        $this->entityManager->flush();

        // Créer une mission dans le pays 2 avec l'agent du pays 1
        $missionData = [
            'name' => 'Mission Test',
            'danger' => 'High',
            'status' => 'InProgress',
            'description' => 'Description de la mission test',
            'objectives' => 'Objectifs de la mission test',
            'startDate' => '2024-01-15',
            'country' => '/api/countries/' . $country2->getId(),
            'agents' => [
                '/api/agents/' . $agent->getId()
            ]
        ];

        $this->client->request(
            'POST',
            '/api/missions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($missionData)
        );

        // Vérifier que la requête échoue avec le bon message d'erreur
        $this->assertResponseStatusCodeSame(400); // L'exception est levée dans le handler CQS
        $responseContent = $this->client->getResponse()->getContent();
        $json = json_decode($responseContent, true);
        
        // Vérifier que le message d'erreur contient les informations attendues
        $this->assertArrayHasKey('error', $json, 'Response should have error key');
        $this->assertStringContainsString('Agent001', $json['error']);
        $this->assertStringContainsString('n\'est pas infiltré dans le pays de la mission', $json['error']);
    }

    public function testPatchMissionWithValidAgentSucceeds(): void
    {
        // Créer un pays
        $country = $this->createTestCountry('France');

        // Créer un agent infiltré dans ce pays
        $agent = $this->createTestAgent('Agent001', 'John', 'Doe', AgentStatus::Available, $country);

        // Créer une mission sans agent
        $mission = $this->createTestMission('Mission Test', $country);

        $this->entityManager->flush();

        // Ajouter l'agent à la mission via PATCH
        $patchData = [
            'agents' => [
                '/api/agents/' . $agent->getId()
            ]
        ];

        $this->client->request(
            'PATCH',
            '/api/missions/' . $mission->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode($patchData)
        );

        // Vérifier que la requête réussit
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Mission updated successfully', $responseData['message']);
    }

    public function testPatchMissionWithInvalidAgentReturnsError(): void
    {
        // Créer deux pays différents
        $country1 = $this->createTestCountry('France');
        $country2 = $this->createTestCountry('Allemagne');

        // Créer un agent infiltré dans le pays 1
        $agent = $this->createTestAgent('Agent001', 'John', 'Doe', AgentStatus::Available, $country1);

        // Créer une mission dans le pays 2
        $mission = $this->createTestMission('Mission Test', $country2);

        $this->entityManager->flush();

        // Essayer d'ajouter l'agent du pays 1 à la mission du pays 2
        $patchData = [
            'agents' => [
                '/api/agents/' . $agent->getId()
            ]
        ];

        $this->client->request(
            'PATCH',
            '/api/missions/' . $mission->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode($patchData)
        );

        // Vérifier que la requête échoue avec le bon message d'erreur
        $this->assertResponseStatusCodeSame(400); // L'exception est levée dans le handler CQS
        $responseContent = $this->client->getResponse()->getContent();
        
        $json = json_decode($responseContent, true);
        
        // Vérifier que le message d'erreur contient les informations attendues
        $this->assertArrayHasKey('error', $json, 'Response should have error key');
        $this->assertStringContainsString('Agent001', $json['error']);
        $this->assertStringContainsString('n\'est pas infiltré dans le pays de la mission', $json['error']);
    }

    public function testPatchNonExistentMissionReturnsNotFound(): void
    {
        $this->client->request(
            'PATCH',
            '/api/missions/99999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode(['name' => 'Updated Mission'])
        );

        $this->assertResponseStatusCodeSame(400);
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);
        
        // Debug: afficher la réponse pour comprendre le format
        if ($responseData === null) {
            $this->fail('Response is not valid JSON: ' . $responseContent);
        }
        
        $this->assertArrayHasKey('error', $responseData, 'Response should have error key');
        $this->assertEquals('Mission not found', $responseData['error']);
    }



    private function createTestCountry(string $name): Country
    {
        $country = new Country();
        $country->setName($name);
        $country->setDanger(DangerLevel::Medium);
        $country->setNumberOfAgents(0);

        $this->entityManager->persist($country);
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
        return $agent;
    }

    private function createTestMission(string $name, Country $country): Mission
    {
        $mission = new Mission();
        $mission->setName($name);
        $mission->setDanger(DangerLevel::Medium);
        $mission->setStatus(MissionStatus::InProgress);
        $mission->setDescription('Description test');
        $mission->setObjectives('Objectifs test');
        $mission->setStartDate(new \DateTimeImmutable('2024-01-15'));
        $mission->setCountry($country);

        $this->entityManager->persist($mission);
        return $mission;
    }
} 