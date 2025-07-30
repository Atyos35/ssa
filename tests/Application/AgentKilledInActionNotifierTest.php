<?php

namespace App\Tests\Application;

use App\Application\AgentKilledInActionNotifier;
use App\Application\AgentDeathNotifier;
use App\Entity\Agent;
use App\Entity\AgentStatus;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ApiPlatform\Metadata\Operation;

class AgentKilledInActionNotifierTest extends TestCase
{
    public function testProcessNotifiesOnKilledInAction()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $notifier = $this->createMock(AgentDeathNotifier::class);
        $agent = $this->createMock(Agent::class);
        $agent->method('getStatus')->willReturn(AgentStatus::KilledInAction);
        $operation = $this->createMock(Operation::class);

        // On attend la suppression des messages puis la notification
        $notifier->expects($this->once())->method('deleteAllMessagesOfAgent')->with($agent);
        $notifier->expects($this->once())->method('notifyAllAgentsOnDeath')->with($agent);
        $em->expects($this->once())->method('persist')->with($agent);
        $em->expects($this->once())->method('flush');

        // Exécution
        $processor = new AgentKilledInActionNotifier($em, $notifier);
        $processor->process($agent, $operation);
    }

    public function testProcessDoesNotNotifyOnOtherStatus()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $notifier = $this->createMock(AgentDeathNotifier::class);
        $agent = $this->createMock(Agent::class);
        $agent->method('getStatus')->willReturn(AgentStatus::Available);
        $operation = $this->createMock(Operation::class);

        // On attend qu'aucune notification ne soit envoyée
        $notifier->expects($this->never())->method('notifyAllAgentsOnDeath');
        $em->expects($this->once())->method('persist')->with($agent);
        $em->expects($this->once())->method('flush');

        // Exécution
        $processor = new AgentKilledInActionNotifier($em, $notifier);
        $processor->process($agent, $operation);
    }
}