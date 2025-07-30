<?php

namespace App\Tests\Application\Agent\Notifier;

use App\Application\Agent\Notifier\AgentDeathNotifier;
use App\Entity\Agent;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class AgentDeathNotifierTest extends TestCase
{
    public function testNotifyAllAgentsOnDeathCreatesMessagesForAllAgentsExceptDeadAgent()
    {
        // On prépare les mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $em->method('getRepository')->willReturn($repo);

        // On crée des UUIDs pour simuler les identifiants
        $deadAgentUuid = Uuid::v4();
        $agent1Uuid = Uuid::v4();
        $agent2Uuid = Uuid::v4();

        $deadAgent = $this->createMock(Agent::class);
        $deadAgent->method('getId')->willReturn($deadAgentUuid);
        $deadAgent->method('getCodeName')->willReturn('Alpha');

        $agent1 = $this->createMock(Agent::class);
        $agent1->method('getId')->willReturn($agent1Uuid);
        $agent2 = $this->createMock(Agent::class);
        $agent2->method('getId')->willReturn($agent2Uuid);

        // Le repository retourne tous les agents (y compris le mort)
        $repo->method('findAll')->willReturn([$deadAgent, $agent1, $agent2]);

        // On attend que persist soit appelé pour chaque agent vivant
        $em->expects($this->exactly(2))->method('persist')->with($this->callback(function ($message) use ($deadAgent) {
            return $message instanceof Message && $message->getBy() === $deadAgent;
        }));
        $em->expects($this->once())->method('flush');

        // On exécute la logique
        $notifier = new AgentDeathNotifier($em);
        $notifier->notifyAllAgentsOnDeath($deadAgent);
    }

    public function testDeleteAllMessagesOfAgentRemovesMessagesAsRecipient()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $messageRepo = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $em->method('getRepository')->willReturn($messageRepo);
        $messageRepo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // Création de l'agent mort
        $deadAgent = $this->createMock(Agent::class);
        $deadAgent->method('getId')->willReturn(Uuid::v4());

        // Création des messages où l'agent est destinataire
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);
        
        // Premier appel pour les messages en tant que destinataire
        // Deuxième appel pour les messages en tant qu'auteur (vide)
        $query->method('getResult')
            ->willReturnOnConsecutiveCalls([$message1, $message2], []);

        // On attend que remove soit appelé pour chaque message destinataire
        $em->expects($this->exactly(2))->method('remove');
        $em->expects($this->once())->method('flush');

        // Exécution
        $notifier = new AgentDeathNotifier($em);
        $notifier->deleteAllMessagesOfAgent($deadAgent);
    }

    public function testDeleteAllMessagesOfAgentRemovesMessagesAsAuthor()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $messageRepo = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $em->method('getRepository')->willReturn($messageRepo);
        $messageRepo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // Création de l'agent mort
        $deadAgent = $this->createMock(Agent::class);
        $deadAgent->method('getId')->willReturn(Uuid::v4());

        // Création des messages où l'agent est auteur
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);
        $message3 = $this->createMock(Message::class);
        
        // Premier appel pour les messages en tant que destinataire (vide)
        // Deuxième appel pour les messages en tant qu'auteur
        $query->method('getResult')
            ->willReturnOnConsecutiveCalls([], [$message1, $message2, $message3]);

        // On attend que remove soit appelé pour chaque message
        $em->expects($this->exactly(3))->method('remove');
        $em->expects($this->once())->method('flush');

        // Exécution
        $notifier = new AgentDeathNotifier($em);
        $notifier->deleteAllMessagesOfAgent($deadAgent);
    }

    public function testDeleteAllMessagesOfAgentRemovesBothRecipientAndAuthorMessages()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $messageRepo = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $em->method('getRepository')->willReturn($messageRepo);
        $messageRepo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // Création de l'agent mort
        $deadAgent = $this->createMock(Agent::class);
        $deadAgent->method('getId')->willReturn(Uuid::v4());

        // Création des messages
        $recipientMessage1 = $this->createMock(Message::class);
        $recipientMessage2 = $this->createMock(Message::class);
        $authorMessage1 = $this->createMock(Message::class);
        $authorMessage2 = $this->createMock(Message::class);
        
        // Premier appel pour les messages en tant que destinataire
        // Deuxième appel pour les messages en tant qu'auteur
        $query->method('getResult')
            ->willReturnOnConsecutiveCalls([$recipientMessage1, $recipientMessage2], [$authorMessage1, $authorMessage2]);

        // On attend que remove soit appelé pour tous les messages (4 au total)
        $em->expects($this->exactly(4))->method('remove');
        $em->expects($this->once())->method('flush');

        // Exécution
        $notifier = new AgentDeathNotifier($em);
        $notifier->deleteAllMessagesOfAgent($deadAgent);
    }

    public function testDeleteAllMessagesOfAgentDoesNothingWhenNoMessages()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $messageRepo = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $em->method('getRepository')->willReturn($messageRepo);
        $messageRepo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // Création de l'agent mort
        $deadAgent = $this->createMock(Agent::class);
        $deadAgent->method('getId')->willReturn(Uuid::v4());

        // Aucun message trouvé
        $query->method('getResult')->willReturn([]);

        // On attend qu'aucun remove ne soit appelé
        $em->expects($this->never())->method('remove');
        $em->expects($this->once())->method('flush');

        // Exécution
        $notifier = new AgentDeathNotifier($em);
        $notifier->deleteAllMessagesOfAgent($deadAgent);
    }
}