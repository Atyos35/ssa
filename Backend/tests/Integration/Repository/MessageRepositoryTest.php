<?php

namespace App\Tests\Integration\Repository;

use App\Domain\Entity\Message;
use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use App\Infrastructure\Persistence\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class MessageRepositoryTest extends KernelTestCase
{
    use ResetDatabase;

    private EntityManagerInterface $entityManager;
    private MessageRepository $messageRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->messageRepository = static::getContainer()->get(MessageRepository::class);
    }

    private function createTestAgent(string $codeName, string $email): Agent
    {
        $country = new Country();
        $country->setName('Test Country');
        $country->setDanger(DangerLevel::Low);
        
        $agent = new Agent();
        $agent->setCodeName($codeName);
        $agent->setFirstName('Test');
        $agent->setLastName('Agent');
        $agent->setPassword('password123');
        $agent->setEmail($email);
        $agent->setYearsOfExperience(5);
        $agent->setStatus(AgentStatus::Available);
        $agent->setEnrolementDate(new \DateTimeImmutable());
        $agent->setInfiltratedCountry($country);
        
        $this->entityManager->persist($country);
        $this->entityManager->persist($agent);
        
        return $agent;
    }

    public function testFindByRecipient(): void
    {
        // Arrange
        $sender = $this->createTestAgent('Sender001', 'sender@test.com');
        $recipient = $this->createTestAgent('Recipient001', 'recipient@test.com');
        
        $message = new Message();
        $message->setTitle('Test Message');
        $message->setBody('This is a test message');
        $message->setBy($sender);
        $message->setRecipient($recipient);
        $message->setCreatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Act
        $messages = $this->messageRepository->findByRecipient($recipient);
        
        // Assert
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertEquals('Test Message', $messages[0]->getTitle());
        $this->assertEquals($recipient->getId(), $messages[0]->getRecipient()->getId());
    }

    public function testFindBySender(): void
    {
        // Arrange
        $sender = $this->createTestAgent('Sender002', 'sender2@test.com');
        $recipient = $this->createTestAgent('Recipient002', 'recipient2@test.com');
        
        $message = new Message();
        $message->setTitle('Sent Message');
        $message->setBody('This message was sent');
        $message->setBy($sender);
        $message->setRecipient($recipient);
        $message->setCreatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Act
        $messages = $this->messageRepository->findBySender($sender);
        
        // Assert
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertEquals('Sent Message', $messages[0]->getTitle());
        $this->assertEquals($sender->getId(), $messages[0]->getBy()->getId());
    }

    public function testFindByRecipientAndSender(): void
    {
        // Arrange
        $sender = $this->createTestAgent('Sender003', 'sender3@test.com');
        $recipient = $this->createTestAgent('Recipient003', 'recipient3@test.com');
        $otherAgent = $this->createTestAgent('Other003', 'other3@test.com');
        
        $message1 = new Message();
        $message1->setTitle('Message from Sender to Recipient');
        $message1->setBody('Targeted message');
        $message1->setBy($sender);
        $message1->setRecipient($recipient);
        $message1->setCreatedAt(new \DateTimeImmutable());
        
        $message2 = new Message();
        $message2->setTitle('Message from Other to Recipient');
        $message2->setBody('Different sender');
        $message2->setBy($otherAgent);
        $message2->setRecipient($recipient);
        $message2->setCreatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($message1);
        $this->entityManager->persist($message2);
        $this->entityManager->flush();

        // Act
        $messages = $this->messageRepository->findByRecipientAndSender($recipient, $sender);
        
        // Assert
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertEquals('Message from Sender to Recipient', $messages[0]->getTitle());
        $this->assertEquals($sender->getId(), $messages[0]->getBy()->getId());
        $this->assertEquals($recipient->getId(), $messages[0]->getRecipient()->getId());
    }

    public function testFindByTitle(): void
    {
        // Arrange
        $sender = $this->createTestAgent('Sender005', 'sender5@test.com');
        $recipient = $this->createTestAgent('Recipient005', 'recipient5@test.com');
        
        $message = new Message();
        $message->setTitle('Unique Title');
        $message->setBody('Message with unique title');
        $message->setBy($sender);
        $message->setRecipient($recipient);
        $message->setCreatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Act
        $messages = $this->messageRepository->findByTitle('Unique Title');
        
        // Assert
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertEquals('Unique Title', $messages[0]->getTitle());
    }

    public function testFindByBodyContaining(): void
    {
        // Arrange
        $sender = $this->createTestAgent('Sender006', 'sender6@test.com');
        $recipient = $this->createTestAgent('Recipient006', 'recipient6@test.com');
        
        $message1 = new Message();
        $message1->setTitle('Message 1');
        $message1->setBody('This message contains the keyword SECRET');
        $message1->setBy($sender);
        $message1->setRecipient($recipient);
        $message1->setCreatedAt(new \DateTimeImmutable());
        
        $message2 = new Message();
        $message2->setTitle('Message 2');
        $message2->setBody('This message does not contain the word');
        $message2->setBy($sender);
        $message2->setRecipient($recipient);
        $message2->setCreatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($message1);
        $this->entityManager->persist($message2);
        $this->entityManager->flush();

        // Act
        $messages = $this->messageRepository->findByBodyContaining('SECRET');
        
        // Assert
        $this->assertIsArray($messages);
        $this->assertCount(1, $messages);
        $this->assertEquals('Message 1', $messages[0]->getTitle());
        $this->assertStringContainsString('SECRET', $messages[0]->getBody());
    }

    public function testDeleteAllByAgent(): void
    {
        // Arrange
        $agent = $this->createTestAgent('Agent007', 'agent007@test.com');
        $otherAgent = $this->createTestAgent('Other007', 'other007@test.com');
        
        $message1 = new Message();
        $message1->setTitle('Message sent by agent');
        $message1->setBody('Sent message');
        $message1->setBy($agent);
        $message1->setRecipient($otherAgent);
        $message1->setCreatedAt(new \DateTimeImmutable());
        
        $message2 = new Message();
        $message2->setTitle('Message received by agent');
        $message2->setBody('Received message');
        $message2->setBy($otherAgent);
        $message2->setRecipient($agent);
        $message2->setCreatedAt(new \DateTimeImmutable());
        
        $message3 = new Message();
        $message3->setTitle('Message between others');
        $message3->setBody('Not related to agent');
        $message3->setBy($otherAgent);
        $message3->setRecipient($otherAgent);
        $message3->setCreatedAt(new \DateTimeImmutable());
        
        $this->entityManager->persist($message1);
        $this->entityManager->persist($message2);
        $this->entityManager->persist($message3);
        $this->entityManager->flush();

        // Act
        $deletedCount = $this->messageRepository->deleteAllByAgent($agent);
        
        // Assert
        $this->assertEquals(2, $deletedCount); // 2 messages supprimés (sent + received)
        
        // Vérifier que le message entre autres agents existe toujours
        $remainingMessages = $this->messageRepository->findAll();
        $this->assertCount(1, $remainingMessages);
        $this->assertEquals('Message between others', $remainingMessages[0]->getTitle());
    }

    public function testFindWithPagination(): void
    {
        // Arrange
        $sender = $this->createTestAgent('Sender008', 'sender8@test.com');
        $recipient = $this->createTestAgent('Recipient008', 'recipient8@test.com');
        
        // Créer plusieurs messages
        for ($i = 1; $i <= 5; $i++) {
            $message = new Message();
            $message->setTitle("Message {$i}");
            $message->setBody("Body of message {$i}");
            $message->setBy($sender);
            $message->setRecipient($recipient);
            $message->setCreatedAt(new \DateTimeImmutable());
            
            $this->entityManager->persist($message);
        }
        $this->entityManager->flush();

        // Act - Page 1, limite 3
        $messages = $this->messageRepository->findWithPagination(1, 3, $recipient);
        
        // Assert
        $this->assertIsArray($messages);
        $this->assertLessThanOrEqual(3, count($messages));
        $this->assertGreaterThan(0, count($messages));
        
        // Vérifier que tous les messages retournés sont pour le bon destinataire
        foreach ($messages as $message) {
            $this->assertEquals($recipient->getId(), $message->getRecipient()->getId());
        }
    }
}
