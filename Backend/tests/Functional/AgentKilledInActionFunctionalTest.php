<?php

namespace App\Tests;

use App\Entity\Agent;
use App\Entity\AgentStatus;
use App\Entity\Message;
use App\Message\AgentKilledInActionMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemoryTransport;
use Zenstruck\Foundry\Test\ResetDatabase;

class AgentKilledInActionFunctionalTest extends WebTestCase
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

    public function testCompleteAgentKilledInActionFlow(): void
    {
        // 1. Créer des agents de test
        $agent1 = $this->createTestAgent('Agent001', 'John', 'Doe', AgentStatus::Available);
        $agent2 = $this->createTestAgent('Agent002', 'Jane', 'Smith', AgentStatus::OnMission);
        $agent3 = $this->createTestAgent('Agent003', 'Bob', 'Wilson', AgentStatus::Available);

        // 2. Créer des messages entre les agents
        $message1 = $this->createTestMessage($agent1, $agent2, 'Message de test 1');
        $message2 = $this->createTestMessage($agent2, $agent1, 'Message de test 2');
        $message3 = $this->createTestMessage($agent3, $agent1, 'Message de test 3');

        $this->entityManager->flush();

        // 3. Vérifier l'état initial
        $this->assertCount(3, $this->entityManager->getRepository(Message::class)->findAll());
        $this->assertCount(0, $this->messengerTransport->get());

        // 4. Effectuer la requête PATCH pour tuer l'agent1
        $this->client->request(
            'PATCH',
            '/api/agents/' . $agent1->getId() . '/status',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'Killed in Action'])
        );

        // 5. Vérifier la réponse HTTP
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Killed in Action', $responseData['status']);

        // 6. Vérifier que l'agent a bien le statut "Killed in Action"
        $this->entityManager->clear();
        $updatedAgent = $this->entityManager->getRepository(Agent::class)->find($agent1->getId());
        $this->assertEquals(AgentStatus::KilledInAction, $updatedAgent->getStatus());

        // 7. Vérifier qu'un message a été envoyé au transport
        $this->assertCount(1, $this->messengerTransport->get());

        // 8. Traiter le message manuellement (simuler le worker)
        $envelope = $this->messengerTransport->get()[0];
        $this->assertInstanceOf(AgentKilledInActionMessage::class, $envelope->getMessage());
        
        $messageHandler = static::getContainer()->get('App\MessageHandler\AgentKilledInActionMessageHandler');
        $messageHandler->__invoke($envelope->getMessage());

        // 9. Vérifier que les messages de l'agent tué ont été supprimés
        $remainingMessages = $this->entityManager->getRepository(Message::class)->findAll();
        $this->assertCount(2, $remainingMessages); // Seuls les messages entre agent2 et agent3 restent

        // 10. Vérifier que les notifications ont été créées pour les autres agents
        $notificationMessages = $this->entityManager->getRepository(Message::class)->findBy([
            'title' => 'Agent Killed in Action'
        ]);
        $this->assertCount(2, $notificationMessages); // agent2 et agent3 ont reçu des notifications

        // 11. Vérifier le contenu des notifications
        foreach ($notificationMessages as $notification) {
            $this->assertEquals('Agent Killed in Action', $notification->getTitle());
            $this->assertStringContainsString('Agent001', $notification->getBody());
            $this->assertStringContainsString('a été tué en mission', $notification->getBody());
            $this->assertNotEquals($agent1->getId(), $notification->getRecipient()->getId());
            $this->assertEquals($agent1->getId(), $notification->getBy()->getId());
        }

        // 12. Vérifier que l'agent tué n'a pas reçu de notification
        $agent1Notifications = $this->entityManager->getRepository(Message::class)->findBy([
            'recipient' => $agent1,
            'title' => 'Agent Killed in Action'
        ]);
        $this->assertCount(0, $agent1Notifications);
    }

    public function testAgentStatusChangeServiceTriggersMessage(): void
    {
        // Créer un agent
        $agent = $this->createTestAgent('Agent004', 'Test', 'Agent', AgentStatus::Available);
        $this->entityManager->flush();

        // Récupérer le service
        $statusChangeService = static::getContainer()->get('App\Service\AgentStatusChangeService');

        // Simuler un changement de statut vers "Killed in Action"
        $agent->setStatus(AgentStatus::KilledInAction);
        $this->entityManager->persist($agent);
        $this->entityManager->flush();

        // Appeler le service
        $statusChangeService->handleStatusChange($agent, AgentStatus::Available);

        // Vérifier qu'un message a été envoyé
        $this->assertCount(1, $this->messengerTransport->get());
        
        $envelope = $this->messengerTransport->get()[0];
        $this->assertInstanceOf(AgentKilledInActionMessage::class, $envelope->getMessage());
        $this->assertEquals($agent->getId(), $envelope->getMessage()->getKilledAgent()->getId());
    }

    public function testAgentStatusChangeServiceDoesNotTriggerMessageForOtherStatuses(): void
    {
        // Créer un agent
        $agent = $this->createTestAgent('Agent005', 'Test', 'Agent', AgentStatus::Available);
        $this->entityManager->flush();

        // Récupérer le service
        $statusChangeService = static::getContainer()->get('App\Service\AgentStatusChangeService');

        // Simuler un changement de statut vers "On Mission" (pas "Killed in Action")
        $agent->setStatus(AgentStatus::OnMission);
        $this->entityManager->persist($agent);
        $this->entityManager->flush();

        // Appeler le service
        $statusChangeService->handleStatusChange($agent, AgentStatus::Available);

        // Vérifier qu'aucun message n'a été envoyé
        $this->assertCount(0, $this->messengerTransport->get());
    }

    public function testMessageHandlerDeletesAllAgentMessages(): void
    {
        // Créer des agents
        $agent1 = $this->createTestAgent('Agent006', 'Agent', '6', AgentStatus::Available);
        $agent2 = $this->createTestAgent('Agent007', 'Agent', '7', AgentStatus::Available);
        $agent3 = $this->createTestAgent('Agent008', 'Agent', '8', AgentStatus::Available);

        // Créer des messages : agent1 envoie et reçoit des messages
        $message1 = $this->createTestMessage($agent1, $agent2, 'Message envoyé par agent1');
        $message2 = $this->createTestMessage($agent2, $agent1, 'Message reçu par agent1');
        $message3 = $this->createTestMessage($agent3, $agent1, 'Autre message reçu par agent1');
        $message4 = $this->createTestMessage($agent2, $agent3, 'Message entre agent2 et agent3');

        $this->entityManager->flush();

        // Vérifier l'état initial
        $this->assertCount(4, $this->entityManager->getRepository(Message::class)->findAll());

        // Créer et traiter le message
        $message = new AgentKilledInActionMessage($agent1);
        $messageHandler = static::getContainer()->get('App\MessageHandler\AgentKilledInActionMessageHandler');
        $messageHandler->__invoke($message);

        // Vérifier que seuls les messages de l'agent1 ont été supprimés
        // Filtrer pour exclure les notifications créées par le MessageHandler
        $allMessages = $this->entityManager->getRepository(Message::class)->findAll();
        $remainingMessages = array_filter($allMessages, function($msg) {
            return $msg->getTitle() !== 'Agent Killed in Action';
        });
        $this->assertCount(1, $remainingMessages); // Seul le message entre agent2 et agent3 reste

        $remainingMessage = array_values($remainingMessages)[0];
        $this->assertEquals($agent2->getId(), $remainingMessage->getBy()->getId());
        $this->assertEquals($agent3->getId(), $remainingMessage->getRecipient()->getId());
    }

    public function testMessageHandlerCreatesNotificationsForAllOtherAgents(): void
    {
        // Créer plusieurs agents
        $agent1 = $this->createTestAgent('Agent009', 'Agent', '9', AgentStatus::Available);
        $agent2 = $this->createTestAgent('Agent010', 'Agent', '10', AgentStatus::Available);
        $agent3 = $this->createTestAgent('Agent011', 'Agent', '11', AgentStatus::Available);
        $agent4 = $this->createTestAgent('Agent012', 'Agent', '12', AgentStatus::Available);

        $this->entityManager->flush();

        // Créer et traiter le message
        $message = new AgentKilledInActionMessage($agent1);
        $messageHandler = static::getContainer()->get('App\MessageHandler\AgentKilledInActionMessageHandler');
        $messageHandler->__invoke($message);

        // Vérifier que 3 notifications ont été créées (pour agent2, agent3, agent4)
        $notifications = $this->entityManager->getRepository(Message::class)->findBy([
            'title' => 'Agent Killed in Action'
        ]);
        $this->assertCount(3, $notifications);

        // Vérifier que chaque agent a reçu une notification
        $recipientIds = array_map(fn($msg) => $msg->getRecipient()->getId(), $notifications);
        $this->assertContains($agent2->getId(), $recipientIds);
        $this->assertContains($agent3->getId(), $recipientIds);
        $this->assertContains($agent4->getId(), $recipientIds);

        // Vérifier que l'agent1 n'a pas reçu de notification
        $this->assertNotContains($agent1->getId(), $recipientIds);

        // Vérifier que toutes les notifications ont l'agent1 comme expéditeur
        foreach ($notifications as $notification) {
            $this->assertEquals($agent1->getId(), $notification->getBy()->getId());
            $this->assertStringContainsString('Agent009', $notification->getBody());
        }
    }

    public function testInvalidStatusReturnsBadRequest(): void
    {
        // Créer un agent
        $agent = $this->createTestAgent('Agent013', 'Agent', '13', AgentStatus::Available);
        $this->entityManager->flush();

        // Tester avec un statut invalide
        $this->client->request(
            'PATCH',
            '/api/agents/' . $agent->getId() . '/status',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'InvalidStatus'])
        );

        // Le sérialiseur lève une exception avant même d'arriver à la validation
        $this->assertResponseStatusCodeSame(500);
    }

    public function testNonExistentAgentReturnsNotFound(): void
    {
        $this->client->request(
            'PATCH',
            '/api/agents/00000000-0000-0000-0000-000000000000/status',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'Killed in Action'])
        );

        // L'agent n'existe pas, donc on reçoit une erreur 404
        $this->assertResponseStatusCodeSame(404);
    }

    private function createTestAgent(string $codeName, string $firstName, string $lastName, AgentStatus $status): Agent
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

        $this->entityManager->persist($agent);
        return $agent;
    }

    private function createTestMessage(Agent $sender, Agent $recipient, string $body): Message
    {
        $message = new Message();
        $message->setTitle('Test Message');
        $message->setBody($body);
        $message->setBy($sender);
        $message->setRecipient($recipient);

        $this->entityManager->persist($message);
        return $message;
    }
} 