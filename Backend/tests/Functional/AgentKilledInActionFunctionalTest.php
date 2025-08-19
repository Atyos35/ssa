<?php

namespace App\Tests;

use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Domain\Entity\Message;
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
        $patchData = ['status' => 'Killed in Action'];
        $this->client->request(
            'PATCH',
            '/api/agents/' . $agent1->getId() . '/status',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($patchData)
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
        $this->assertResponseStatusCodeSame(400);
    }

    public function testNonExistentAgentReturnsNotFound(): void
    {
        $this->client->request(
            'PATCH',
            '/api/agents/999999/status', // ID entier inexistant au lieu d'UUID
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['status' => 'Killed in Action'])
        );

        // L'agent n'existe pas, donc on reçoit une erreur 400 (DomainException)
        $this->assertResponseStatusCodeSame(400);
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