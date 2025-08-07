<?php

namespace App\Tests\Functional;

use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionResult;
use App\Domain\Entity\MissionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class MissionFunctionalTest extends WebTestCase
{
    use ResetDatabase;

    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testGetMissionsList(): void
    {
        // Créer des données de test
        $country = $this->createTestCountry('France', 'FR');
        $agent = $this->createTestAgent('Agent001', 'John', 'Doe', AgentStatus::Available, $country);
        
        $mission1 = $this->createTestMission('Mission 1', DangerLevel::High, MissionStatus::InProgress, $country, [$agent]);
        $mission2 = $this->createTestMission('Mission 2', DangerLevel::Medium, MissionStatus::Success, $country, [$agent]);
        
        // Créer un résultat de mission pour mission2
        $missionResult = new MissionResult();
        $missionResult->setStatus(MissionStatus::Success);
        $missionResult->setSummary('Mission accomplie avec succès');
        $missionResult->setMission($mission2);
        $mission2->setMissionResult($missionResult);
        
        $this->entityManager->persist($missionResult);
        $this->entityManager->flush();

        // Tester GET /api/missions
        $this->client->request('GET', '/api/missions');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData);
        
        // Vérifier que les missions sont retournées avec leurs résultats
        $missionWithResult = null;
        foreach ($responseData as $mission) {
            if ($mission['name'] === 'Mission 2') {
                $missionWithResult = $mission;
                break;
            }
        }
        
        $this->assertNotNull($missionWithResult);
        $this->assertArrayHasKey('missionResult', $missionWithResult);
        $this->assertEquals('Success', $missionWithResult['missionResult']['status']);
        $this->assertEquals('Mission accomplie avec succès', $missionWithResult['missionResult']['summary']);
    }

    public function testGetMissionById(): void
    {
        // Créer des données de test
        $country = $this->createTestCountry('Allemagne', 'DE');
        $agent = $this->createTestAgent('Agent002', 'Jane', 'Smith', AgentStatus::Available, $country);
        $mission = $this->createTestMission('Mission Test', DangerLevel::Low, MissionStatus::InProgress, $country, [$agent]);

        // Tester GET /api/missions/{id}
        $this->client->request('GET', '/api/missions/' . $mission->getId());

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals('Mission Test', $responseData['name']);
        $this->assertEquals('Low', $responseData['danger']);
        $this->assertEquals('InProgress', $responseData['status']);
        $this->assertArrayHasKey('country', $responseData);
        $this->assertArrayHasKey('agents', $responseData);
        $this->assertEmpty($responseData['agents']); // Pas d'agents pour des raisons de sécurité
        $this->assertArrayHasKey('missionResult', $responseData);
        $this->assertNull($responseData['missionResult']); // Pas de résultat pour cette mission
    }

    public function testPatchMissionWithStatusAndResultSummary(): void
    {
        // Créer des données de test
        $country = $this->createTestCountry('Espagne', 'ES');
        $agent = $this->createTestAgent('Agent003', 'Bob', 'Wilson', AgentStatus::Available, $country);
        $mission = $this->createTestMission('Mission à mettre à jour', DangerLevel::Medium, MissionStatus::InProgress, $country, [$agent]);

        // Tester PATCH /api/missions/{id} avec statut Success et résumé
        $patchData = [
            'status' => 'Success',
            'missionResultSummary' => 'Mission accomplie avec succès - tous les objectifs atteints'
        ];

        $this->client->request(
            'PATCH',
            '/api/missions/' . $mission->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($patchData)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Mission updated successfully', $responseData['message']);

        // Vérifier que la mission a été mise à jour en base
        $this->entityManager->clear();
        $updatedMission = $this->entityManager->getRepository(Mission::class)->find($mission->getId());
        
        $this->assertEquals(MissionStatus::Success, $updatedMission->getStatus());
        $this->assertNotNull($updatedMission->getMissionResult());
        $this->assertEquals(MissionStatus::Success, $updatedMission->getMissionResult()->getStatus());
        $this->assertEquals('Mission accomplie avec succès - tous les objectifs atteints', $updatedMission->getMissionResult()->getSummary());
        $this->assertNotNull($updatedMission->getEndDate()); // Date de fin automatiquement définie
    }

    public function testPatchMissionWithFailureStatus(): void
    {
        // Créer des données de test
        $country = $this->createTestCountry('Italie', 'IT');
        $agent = $this->createTestAgent('Agent004', 'Alice', 'Brown', AgentStatus::Available, $country);
        $mission = $this->createTestMission('Mission à échouer', DangerLevel::High, MissionStatus::InProgress, $country, [$agent]);

        // Tester PATCH /api/missions/{id} avec statut Failure
        $patchData = [
            'status' => 'Failure',
            'missionResultSummary' => 'Mission échouée - agent capturé'
        ];

        $this->client->request(
            'PATCH',
            '/api/missions/' . $mission->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($patchData)
        );

        $this->assertResponseIsSuccessful();

        // Vérifier que la mission a été mise à jour en base
        $this->entityManager->clear();
        $updatedMission = $this->entityManager->getRepository(Mission::class)->find($mission->getId());
        
        $this->assertEquals(MissionStatus::Failure, $updatedMission->getStatus());
        $this->assertNotNull($updatedMission->getMissionResult());
        $this->assertEquals(MissionStatus::Failure, $updatedMission->getMissionResult()->getStatus());
        $this->assertEquals('Mission échouée - agent capturé', $updatedMission->getMissionResult()->getSummary());
    }

    public function testPatchMissionWithoutResultSummary(): void
    {
        // Créer des données de test
        $country = $this->createTestCountry('Portugal', 'PT');
        $agent = $this->createTestAgent('Agent005', 'Charlie', 'Davis', AgentStatus::Available, $country);
        $mission = $this->createTestMission('Mission sans résumé', DangerLevel::Low, MissionStatus::InProgress, $country, [$agent]);

        // Tester PATCH /api/missions/{id} avec statut Success mais sans résumé
        $patchData = [
            'status' => 'Success'
        ];

        $this->client->request(
            'PATCH',
            '/api/missions/' . $mission->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($patchData)
        );

        $this->assertResponseIsSuccessful();

        // Vérifier que la mission a été mise à jour en base
        $this->entityManager->clear();
        $updatedMission = $this->entityManager->getRepository(Mission::class)->find($mission->getId());
        
        $this->assertEquals(MissionStatus::Success, $updatedMission->getStatus());
        $this->assertNotNull($updatedMission->getMissionResult());
        $this->assertEquals(MissionStatus::Success, $updatedMission->getMissionResult()->getStatus());
        $this->assertNull($updatedMission->getMissionResult()->getSummary()); // Résumé null
    }

    public function testGetMissionNotFound(): void
    {
        $this->client->request('GET', '/api/missions/999999');

        $this->assertResponseStatusCodeSame(400); // Le handler lève une exception
    }

    public function testPatchMissionNotFound(): void
    {
        $patchData = [
            'name' => 'Mission inexistante'
        ];

        $this->client->request(
            'PATCH',
            '/api/missions/999999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($patchData)
        );

        $this->assertResponseStatusCodeSame(400); // Le handler lève une exception DomainException
    }

    private function createTestCountry(string $name, string $code): Country
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

    private function createTestMission(string $name, DangerLevel $danger, MissionStatus $status, Country $country, array $agents = []): Mission
    {
        $mission = new Mission();
        $mission->setName($name);
        $mission->setDanger($danger);
        $mission->setStatus($status);
        $mission->setDescription('Description de ' . $name);
        $mission->setObjectives('Objectifs de ' . $name);
        $mission->setStartDate(new \DateTimeImmutable('2024-01-15'));
        $mission->setCountry($country);

        foreach ($agents as $agent) {
            $mission->addAgent($agent);
        }

        $this->entityManager->persist($mission);
        $this->entityManager->flush();
        return $mission;
    }
} 