<?php

namespace App\Tests\Application;

use App\Application\AgentDeathNotifier;
use App\Entity\Agent;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
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
}