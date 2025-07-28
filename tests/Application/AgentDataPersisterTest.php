<?php

namespace App\Tests\Application;

use App\Application\AgentDataPersister;
use App\Application\AgentDeathNotifier;
use App\Entity\Agent;
use App\Entity\AgentStatus;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Metadata\Operation;
use PHPUnit\Framework\TestCase;

class AgentDataPersisterTest extends TestCase
{
    /**
     * test que le notifier est appelé dans le status de l'agent passe à 'Killed in Action'.
     * on test aussi que l'agent est persisté et flushé.
     */
    public function testProcessCallsNotifierOnKilledInAction()
    {
        // on créé les mocks des dépendances
        $em = $this->createMock(EntityManagerInterface::class);
        $notifier = $this->createMock(AgentDeathNotifier::class);
        $agent = $this->createMock(Agent::class);
        $operation = $this->createMock(Operation::class);

        // on simule le status de l'agent à 'Killed in Action'
        $agent->method('getStatus')->willReturn(AgentStatus::KilledInAction);
        // On attend que le notifier soit appelé une fois avec l'agent
        $notifier->expects($this->once())
            ->method('notifyAllAgentsOnDeath')
            ->with($agent);
        // On attend que l'agent soit persisté et flushé
        $em->expects($this->once())->method('persist')->with($agent);
        $em->expects($this->once())->method('flush');

        // On exécute la méthode process de l'AgentDataPersister
        $persister = new AgentDataPersister($em, $notifier);
        $result = $persister->process($agent, $operation);
        // On vérifie que le résultat est l'agent
        $this->assertSame($agent, $result);
    }

    /**
     * On test que le notifier n'est pas appelé si le status de l'agent n'est pas 'Killed in Action'.
     * On test aussi que l'agent est persisté et flushé.
     */
    public function testProcessDoesNotCallNotifierIfNotKilledInAction()
    {
        // On créé les mocks des dépendances
        $em = $this->createMock(EntityManagerInterface::class);
        $notifier = $this->createMock(AgentDeathNotifier::class);
        $agent = $this->createMock(Agent::class);
        $operation = $this->createMock(Operation::class);

        // On simule le status de l'agent à 'Available'
        $agent->method('getStatus')->willReturn(AgentStatus::Available);
        // On attend que le notifier ne soit jamais appelé
        $notifier->expects($this->never())
            ->method('notifyAllAgentsOnDeath');
        // On attend que l'agent soit persisté et flushé
        $em->expects($this->once())->method('persist')->with($agent);
        $em->expects($this->once())->method('flush');

        // On exécute la méthode process de l'AgentDataPersister
        $persister = new AgentDataPersister($em, $notifier);
        $result = $persister->process($agent, $operation);
        // On vérifie que le résultat est l'agent
        $this->assertSame($agent, $result);
    }
} 