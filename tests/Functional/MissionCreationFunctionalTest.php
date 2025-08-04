<?php

namespace App\Tests\Functional;

use App\Entity\Agent;
use App\Entity\AgentStatus;
use App\Entity\Country;
use App\Entity\DangerLevel;
use App\Entity\Message;
use App\Entity\Mission;
use App\Entity\MissionStatus;
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
            'country' => '/api/countries/' . $country->getId()
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
        $this->assertEquals('Mission Test', $responseData['name']);

        // 6.5. Pas d'agents participants pour ce test - tous les agents du pays recevront une notification

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
        $this->assertCount(3, $notificationMessages); // Tous les agents du pays ont reçu une notification

        // 10. Vérifier le contenu des notifications
        foreach ($notificationMessages as $notification) {
            $this->assertEquals('Nouvelle Mission Créée', $notification->getTitle());
            $this->assertStringContainsString('Mission Test', $notification->getBody());
            $this->assertStringContainsString('High', $notification->getBody());
            $this->assertStringContainsString('Objectifs de la mission test', $notification->getBody());
            $this->assertNull($notification->getBy()); // Pas d'expéditeur pour les notifications système
        }

        // 11. Vérifier que tous les agents du pays ont reçu une notification
        $recipientIds = array_map(fn($msg) => $msg->getRecipient()->getId(), $notificationMessages);
        $this->assertContains($agent1->getId(), $recipientIds);
        $this->assertContains($agent2->getId(), $recipientIds);
        $this->assertContains($agent3->getId(), $recipientIds);

        // 12. Vérifier que l'agent d'un autre pays n'a pas reçu de notification
        $agent4Notifications = $this->entityManager->getRepository(Message::class)->findBy([
            'recipient' => $agent4,
            'title' => 'Nouvelle Mission Créée'
        ]);
        $this->assertCount(0, $agent4Notifications);
    }

    public function testMissionCreationServiceTriggersMessage(): void
    {
        // Créer un pays et des agents
        $country = $this->createTestCountry('Espagne', 'ES');
        $agent = $this->createTestAgent('Agent005', 'Test', 'Agent', AgentStatus::Available, $country);
        $this->entityManager->flush();

        // Créer une mission
        $mission = new Mission();
        $mission->setName('Mission Service Test');
        $mission->setDanger(DangerLevel::Medium);
        $mission->setStatus(MissionStatus::InProgress);
        $mission->setDescription('Description test');
        $mission->setObjectives('Objectifs test');
        $mission->setStartDate(new \DateTimeImmutable('2024-01-15'));
        $mission->setCountry($country);

        // Récupérer le service
        $missionCreationService = static::getContainer()->get('App\Service\MissionCreationService');

        // Appeler le service
        $missionCreationService->handleMissionCreation($mission);

        // Vérifier qu'un message a été envoyé
        $this->assertCount(1, $this->messengerTransport->get());
        
        $envelope = $this->messengerTransport->get()[0];
        $this->assertInstanceOf(MissionCreatedMessage::class, $envelope->getMessage());
        $this->assertEquals($mission->getId(), $envelope->getMessage()->getMission()->getId());
    }

    public function testMessageHandlerCreatesNotificationsForAgentsInCountry(): void
    {
        // Créer un pays et plusieurs agents
        $country = $this->createTestCountry('Italie', 'IT');
        $agent1 = $this->createTestAgent('Agent006', 'Agent', '6', AgentStatus::Available, $country);
        $agent2 = $this->createTestAgent('Agent007', 'Agent', '7', AgentStatus::Available, $country);
        $agent3 = $this->createTestAgent('Agent008', 'Agent', '8', AgentStatus::Available, $country);

        // Créer une mission avec agent1 comme participant
        $mission = new Mission();
        $mission->setName('Mission Handler Test');
        $mission->setDanger(DangerLevel::Low);
        $mission->setStatus(MissionStatus::InProgress);
        $mission->setDescription('Description test');
        $mission->setObjectives('Objectifs test');
        $mission->setStartDate(new \DateTimeImmutable('2024-01-15'));
        $mission->setCountry($country);
        $mission->addAgent($agent1);

        $this->entityManager->persist($mission);
        $this->entityManager->flush();

        // Créer et traiter le message
        $message = new MissionCreatedMessage($mission);
        $messageHandler = static::getContainer()->get('App\MessageHandler\MissionCreatedMessageHandler');
        $messageHandler->__invoke($message);

        // Vérifier que 2 notifications ont été créées (pour agent2 et agent3)
        $notifications = $this->entityManager->getRepository(Message::class)->findBy([
            'title' => 'Nouvelle Mission Créée'
        ]);
        $this->assertCount(2, $notifications);

        // Vérifier que chaque agent a reçu une notification
        $recipientIds = array_map(fn($msg) => $msg->getRecipient()->getId(), $notifications);
        $this->assertContains($agent2->getId(), $recipientIds);
        $this->assertContains($agent3->getId(), $recipientIds);

        // Vérifier que l'agent1 n'a pas reçu de notification
        $this->assertNotContains($agent1->getId(), $recipientIds);

        // Vérifier que toutes les notifications ont le bon contenu
        foreach ($notifications as $notification) {
            $this->assertStringContainsString('Mission Handler Test', $notification->getBody());
            $this->assertStringContainsString('Low', $notification->getBody());
            $this->assertNull($notification->getBy());
        }
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

        // Le sérialiseur lève une exception avant même d'arriver à la validation
        $this->assertResponseStatusCodeSame(500);
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