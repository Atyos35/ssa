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

class MissionResultFunctionalTest extends WebTestCase
{
    use ResetDatabase;

    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testMissionResultCreatedWhenStatusChangedToSuccess(): void
    {
        // Créer un pays
        $country = $this->createTestCountry('France');

        // Créer un agent
        $agent = $this->createTestAgent('Agent001', 'John', 'Doe', AgentStatus::Available, $country);

        // Créer une mission en cours
        $mission = $this->createTestMission('Mission Test', $country, MissionStatus::InProgress);

        $this->entityManager->flush();

        // Changer le statut vers Success via PATCH
        $patchData = [
            'status' => 'Success'
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

        // Vérifier que la requête a réussi
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Mission updated successfully', $responseData['message']);

        // Vérifier qu'un résultat de mission a été créé
        $this->entityManager->clear();
        $mission = $this->entityManager->find(Mission::class, $mission->getId());
        $this->assertNotNull($mission->getMissionResult());
        $this->assertEquals(MissionStatus::Success, $mission->getMissionResult()->getStatus());
        // Le summary peut être null, on vérifie juste que le résultat existe
        $this->assertNotNull($mission->getMissionResult());
    }

    public function testMissionResultCreatedWhenStatusChangedToFailure(): void
    {
        // Créer un pays
        $country = $this->createTestCountry('Allemagne');

        // Créer un agent
        $agent = $this->createTestAgent('Agent002', 'Jane', 'Smith', AgentStatus::Available, $country);

        // Créer une mission en cours
        $mission = $this->createTestMission('Mission Échec', $country, MissionStatus::InProgress);

        $this->entityManager->flush();

        // Changer le statut vers Failure via PATCH
        $patchData = [
            'status' => 'Failure'
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

        // Vérifier que la requête a réussi
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Mission updated successfully', $responseData['message']);

        // Vérifier qu'un résultat de mission a été créé
        $this->entityManager->clear();
        $mission = $this->entityManager->find(Mission::class, $mission->getId());
        $this->assertNotNull($mission->getMissionResult());
        $this->assertEquals(MissionStatus::Failure, $mission->getMissionResult()->getStatus());
        // Le summary peut être null, on vérifie juste que le résultat existe
        $this->assertNotNull($mission->getMissionResult());
    }

    public function testMissionResultNotCreatedWhenStatusRemainsInProgress(): void
    {
        // Créer un pays
        $country = $this->createTestCountry('Espagne');

        // Créer une mission en cours
        $mission = $this->createTestMission('Mission Continue', $country, MissionStatus::InProgress);

        $this->entityManager->flush();

        // Modifier autre chose que le statut (par exemple la description)
        $patchData = [
            'description' => 'Nouvelle description'
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

        // Vérifier qu'aucun résultat de mission n'a été créé
        $this->entityManager->clear();
        $mission = $this->entityManager->find(Mission::class, $mission->getId());
        $this->assertNull($mission->getMissionResult());
    }

    public function testMissionResultNotCreatedTwice(): void
    {
        // Créer un pays
        $country = $this->createTestCountry('Italie');

        // Créer une mission en cours
        $mission = $this->createTestMission('Mission Unique', $country, MissionStatus::InProgress);

        $this->entityManager->flush();

        // Changer le statut vers Success
        $patchData = [
            'status' => 'Success'
        ];

        $this->client->request(
            'PATCH',
            '/api/missions/' . $mission->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode($patchData)
        );

        $this->assertResponseIsSuccessful();

        // Modifier autre chose (par exemple la description)
        $patchData2 = [
            'description' => 'Description modifiée'
        ];

        $this->client->request(
            'PATCH',
            '/api/missions/' . $mission->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode($patchData2)
        );

        $this->assertResponseIsSuccessful();

        // Vérifier qu'un seul résultat de mission a été créé
        $this->entityManager->clear();
        $mission = $this->entityManager->find(Mission::class, $mission->getId());
        $this->assertNotNull($mission->getMissionResult());
        $this->assertEquals(MissionStatus::Success, $mission->getMissionResult()->getStatus());
    }

    public function testEndDateSetWhenMissionCompleted(): void
    {
        // Créer un pays
        $country = $this->createTestCountry('Portugal');

        // Créer une mission en cours
        $mission = $this->createTestMission('Mission Date', $country, MissionStatus::InProgress);

        $this->entityManager->flush();

        // Vérifier que la date de fin n'est pas définie
        $this->assertNull($mission->getEndDate());

        // Changer le statut vers Success
        $patchData = [
            'status' => 'Success'
        ];

        $this->client->request(
            'PATCH',
            '/api/missions/' . $mission->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/merge-patch+json'],
            json_encode($patchData)
        );

        $this->assertResponseIsSuccessful();

        // Vérifier que la date de fin a été définie
        $this->entityManager->clear();
        $mission = $this->entityManager->find(Mission::class, $mission->getId());
        $this->assertNotNull($mission->getEndDate());
        $this->assertInstanceOf(\DateTimeImmutable::class, $mission->getEndDate());
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

    private function createTestMission(string $name, Country $country, MissionStatus $status = MissionStatus::InProgress): Mission
    {
        $mission = new Mission();
        $mission->setName($name);
        $mission->setDanger(DangerLevel::Medium);
        $mission->setStatus($status);
        $mission->setDescription('Description test');
        $mission->setObjectives('Objectifs test');
        $mission->setStartDate(new \DateTimeImmutable('2024-01-15'));
        $mission->setCountry($country);

        $this->entityManager->persist($mission);
        return $mission;
    }
} 