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
    /**
     * On test que le notifier crée un message pour chaque agent sauf le mort.
     * On test aussi que le message est correctement construit et persisté, et que flush est appelé une fois.
     */
    public function testNotifyAllAgentsOnDeathCreatesMessagesForAllAgentsExceptDead()
    {
        // On créé les mocks des dépendances
        $em = $this->createMock(EntityManagerInterface::class);
        $deadAgent = $this->createMock(Agent::class);
        $agent1 = $this->createMock(Agent::class);
        $agent2 = $this->createMock(Agent::class);

        // On génère des UUIDs uniques pour chaque agent
        $deadUuid = Uuid::v4();
        $agent1Uuid = Uuid::v4();
        $agent2Uuid = Uuid::v4();

        // On configure les agents
        $deadAgent->method('getId')->willReturn($deadUuid);
        $deadAgent->method('getCodeName')->willReturn('007');
        $agent1->method('getId')->willReturn($agent1Uuid);
        $agent2->method('getId')->willReturn($agent2Uuid);

        // On configure le repository pour retourner tous les agents (y compris le mort)
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findAll')->willReturn([$deadAgent, $agent1, $agent2]);
        $em->method('getRepository')->willReturn($repo);

        // On attend que persist soit appelé pour chaque agent vivant (pas le mort)
        $em->expects($this->exactly(2))->method('persist')->with($this->callback(function ($message) use ($deadAgent) {
            // On vérifie que le message est bien formé
            if (!$message instanceof Message) return false;
            $title = $message->getTitle();
            $body = $message->getBody();
            $by = $message->getBy();
            return $title === 'Un agent est mort !' &&
                   str_contains($body, 'Agent 007 a été tué') &&
                   $by === $deadAgent;
        }));
        // On attend que flush soit appelé une fois à la fin
        $em->expects($this->once())->method('flush');

        // On exécute la logique de notification
        $notifier = new AgentDeathNotifier($em);
        $notifier->notifyAllAgentsOnDeath($deadAgent);
    }
} 