<?php

namespace App\Tests\Functional;

use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\Message;
use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionStatus;
use App\Message\MissionCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Zenstruck\Foundry\Test\ResetDatabase;

class MissionCreationFunctionalTest extends WebTestCase
{
    use ResetDatabase;

    private $client;
    private $entityManager;
    private $messengerTransport;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->messengerTransport = static::getContainer()->get('messenger.transport.async');
    }

    public function testCompleteMissionCreationFlow(): void
    {
        // 1. Créer un pays
        $country = $this->createTestCountry('France', 'FR');

        // 2. Créer des agents infiltrés dans ce pays
        $agent1 = $this->createTestAgent('Agent001', 'John', 'Doe', AgentStatus::Available, $country);
        $agent2 = $this->createTestAgent('Agent002', 'Jane', 'Smith', AgentStatus::OnMission, $country);
        $agent3 = $this->createTestAgent('Agent003', 'Bob', 'Wilson', AgentStatus::Available, $country);

        // 3. Créer un agent dans un autre pays
        $otherCountry = $this->createTestCountry('Allemagne', 'DE');
        $agent4 = $this->createTestAgent('Agent004', 'Alice', 'Brown', AgentStatus::Available, $otherCountry);

        $this->entityManager->flush();

        // 4. Vérifier l'état initial
        $this->assertCount(0, $this->entityManager->getRepository(Message::class)->findAll());
        $this->assertCount(0, $this->messengerTransport->get());

        // 5. Créer une mission avec agent1 et agent2 comme participants
        $missionData = [
            'name' => 'Mission Test',
            'danger' => 'High',
            'status' => 'InProgress',
            'description' => 'Description de la mission test',
            'objectives' => 'Objectifs de la mission test',
            'startDate' => '2024-01-15',
            'countryId' => $country->getId(),
            'agentIds' => [$agent1->getId(), $agent2->getId()]
        ];

        $this->client->request(
            'POST',
            '/api/missions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($missionData)
        );

        // 6. Vérifier la réponse HTTP
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Mission created successfully', $responseData['message']);

        // 7. Vérifier qu'un message a été envoyé au transport
        $this->assertCount(1, $this->messengerTransport->get());

        // 8. Traiter le message manuellement (simuler le worker)
        $envelope = $this->messengerTransport->get()[0];
        $this->assertInstanceOf(MissionCreatedMessage::class, $envelope->getMessage());
        
        $messageHandler = static::getContainer()->get('App\MessageHandler\MissionCreatedMessageHandler');
        $messageHandler->__invoke($envelope->getMessage());

        // 9. Vérifier que les notifications ont été créées pour tous les agents du pays
        $notificationMessages = $this->entityManager->getRepository(Message::class)->findBy([
            'title' => 'Nouvelle Mission Créée'
        ]);
        $this->assertCount(1, $notificationMessages); // Un message a été envoyé au transport

        // 10. Vérifier le contenu des notifications
        foreach ($notificationMessages as $notification) {
            $this->assertEquals('Nouvelle Mission Créée', $notification->getTitle());
            $this->assertStringContainsString('Mission Test', $notification->getBody());
            $this->assertStringContainsString('High', $notification->getBody());
            $this->assertStringContainsString('Objectifs de la mission test', $notification->getBody());
            $this->assertNull($notification->getBy()); // Pas d'expéditeur pour les notifications système
        }

        // 11. Vérifier que le message a été envoyé au transport
        $this->assertCount(1, $this->messengerTransport->get());
        
        // 12. Vérifier que l'agent d'un autre pays n'a pas reçu de notification
        $agent4Notifications = $this->entityManager->getRepository(Message::class)->findBy([
            'recipient' => $agent4,
            'title' => 'Nouvelle Mission Créée'
        ]);
        $this->assertCount(0, $agent4Notifications);
    }





    public function testInvalidMissionDataReturnsBadRequest(): void
    {
        // Tester avec des données invalides
        $this->client->request(
            'POST',
            '/api/missions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => '', // Nom vide
                'danger' => 'InvalidDanger', // Danger invalide
                'status' => 'InvalidStatus' // Statut invalide
            ])
        );

        $this->assertResponseStatusCodeSame(400);
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
} 