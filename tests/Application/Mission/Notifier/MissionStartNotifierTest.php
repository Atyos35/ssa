<?php

namespace App\Tests\Application\Mission\Notifier;

use App\Application\Mission\Notifier\MissionStartNotifier;
use App\Entity\Agent;
use App\Entity\Country;
use App\Entity\Message;
use App\Entity\Mission;
use App\Entity\AgentStatus;
use App\Entity\DangerLevel;
use App\Entity\MissionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class MissionStartNotifierTest extends TestCase
{
    public function testNotifyAgentsOnMissionStartSendsMessagesToAgentsInCountryExceptParticipants()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $em->method('getRepository')->willReturn($repo);
        $repo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // Création des UUIDs réels pour les tests
        $participatingAgentUuid = Uuid::v4();
        $nonParticipatingAgentUuid = Uuid::v4();

        // Création des entités avec les bons types d'ID
        $country = $this->createMock(Country::class);
        $country->method('getId')->willReturn(1); // Country utilise int

        $participatingAgent = $this->createMock(Agent::class);
        $participatingAgent->method('getId')->willReturn($participatingAgentUuid); // Agent utilise Uuid
        $participatingAgent->method('getCodeName')->willReturn('007');

        $nonParticipatingAgent = $this->createMock(Agent::class);
        $nonParticipatingAgent->method('getId')->willReturn($nonParticipatingAgentUuid); // Agent utilise Uuid
        $nonParticipatingAgent->method('getCodeName')->willReturn('008');

        $mission = $this->createMock(Mission::class);
        $mission->method('getCountry')->willReturn($country);
        $mission->method('getName')->willReturn('Opération GoldenEye');
        $mission->method('getDanger')->willReturn(DangerLevel::High);
        $mission->method('getAgents')->willReturn(new ArrayCollection([$participatingAgent]));

        // Le repository retourne les agents du pays
        $query->method('getResult')->willReturn([$participatingAgent, $nonParticipatingAgent]);

        // On attend que persist soit appelé pour l'agent non participant
        $em->expects($this->once())->method('persist')->with($this->callback(function ($message) use ($nonParticipatingAgent, $participatingAgent) {
            return $message instanceof Message 
                && $message->getRecipient() === $nonParticipatingAgent
                && $message->getBy() === $participatingAgent
                && $message->getTitle() === 'Nouvelle mission en cours'
                && strpos($message->getBody(), 'Opération GoldenEye') !== false
                && strpos($message->getBody(), 'High') !== false;
        }));
        $em->expects($this->once())->method('flush');

        // Exécution
        $notifier = new MissionStartNotifier($em);
        $notifier->notifyAgentsOnMissionStart($mission);
    }

    public function testNotifyAgentsOnMissionStartDoesNothingWhenNoCountry()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $mission = $this->createMock(Mission::class);
        $mission->method('getCountry')->willReturn(null);

        // On attend qu'aucune opération de base de données ne soit effectuée
        $em->expects($this->never())->method('getRepository');
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        // Exécution
        $notifier = new MissionStartNotifier($em);
        $notifier->notifyAgentsOnMissionStart($mission);
    }

    public function testNotifyAgentsOnMissionStartExcludesKilledInActionAgents()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $em->method('getRepository')->willReturn($repo);
        $repo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // Création des entités
        $country = $this->createMock(Country::class);
        $country->method('getId')->willReturn(1); // Country utilise int

        $mission = $this->createMock(Mission::class);
        $mission->method('getCountry')->willReturn($country);
        $mission->method('getName')->willReturn('Opération GoldenEye');
        $mission->method('getDanger')->willReturn(DangerLevel::High);
        $mission->method('getAgents')->willReturn(new ArrayCollection([]));

        // Le repository ne retourne aucun agent car ils sont tous tués
        $query->method('getResult')->willReturn([]);

        // On attend qu'aucun message ne soit envoyé car aucun agent actif
        $em->expects($this->never())->method('persist');
        $em->expects($this->once())->method('flush');

        // Exécution
        $notifier = new MissionStartNotifier($em);
        $notifier->notifyAgentsOnMissionStart($mission);
    }

    public function testNotifyAgentsOnMissionStartWithNoParticipatingAgents()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $em->method('getRepository')->willReturn($repo);
        $repo->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // Création des entités
        $country = $this->createMock(Country::class);
        $country->method('getId')->willReturn(1); // Country utilise int

        $agent = $this->createMock(Agent::class);
        $agent->method('getId')->willReturn(Uuid::v4()); // Agent utilise Uuid
        $agent->method('getCodeName')->willReturn('009');

        $mission = $this->createMock(Mission::class);
        $mission->method('getCountry')->willReturn($country);
        $mission->method('getName')->willReturn('Opération GoldenEye');
        $mission->method('getDanger')->willReturn(DangerLevel::High);
        $mission->method('getAgents')->willReturn(new ArrayCollection([]));

        // Le repository retourne un agent
        $query->method('getResult')->willReturn([$agent]);

        // On attend que persist soit appelé avec un message dont l'auteur est null
        $em->expects($this->once())->method('persist')->with($this->callback(function ($message) use ($agent) {
            return $message instanceof Message 
                && $message->getRecipient() === $agent
                && $message->getBy() === null;
        }));
        $em->expects($this->once())->method('flush');

        // Exécution
        $notifier = new MissionStartNotifier($em);
        $notifier->notifyAgentsOnMissionStart($mission);
    }
} 