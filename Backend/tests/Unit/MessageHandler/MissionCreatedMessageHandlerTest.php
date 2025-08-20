<?php

namespace App\Tests\Unit\MessageHandler;

use App\MessageHandler\MissionCreatedMessageHandler;
use App\Message\MissionCreatedMessage;
use App\Domain\Entity\Mission;
use App\Domain\Entity\Country;
use App\Domain\Entity\Agent;
use App\Domain\Entity\Message;
use App\Domain\Entity\AgentStatus;
use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\MissionStatus;
use App\Infrastructure\Persistence\Repository\AgentRepository;
use App\Infrastructure\Persistence\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class MissionCreatedMessageHandlerTest extends TestCase
{
    private MissionCreatedMessageHandler $handler;
    private EntityManagerInterface $entityManager;
    private AgentRepository $agentRepository;
    private MissionRepository $missionRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->agentRepository = $this->createMock(AgentRepository::class);
        $this->missionRepository = $this->createMock(MissionRepository::class);
        
        $this->handler = new MissionCreatedMessageHandler(
            $this->entityManager,
            $this->agentRepository,
            $this->missionRepository
        );
    }

    public function testInvokeWithValidMission(): void
    {
        // Arrange
        $country = $this->createMock(Country::class);
        $country->method('getId')->willReturn(1);
        $country->method('getName')->willReturn('Test Country');

        $mission = $this->createMock(Mission::class);
        $mission->method('getId')->willReturn(1);
        $mission->method('getName')->willReturn('Test Mission');
        $mission->method('getCountry')->willReturn($country);
        $mission->method('getAgents')->willReturn($this->createMock(\Doctrine\Common\Collections\Collection::class)); // Pas d'agents participants

        $missionFromDb = $this->createMock(Mission::class);
        $missionFromDb->method('getId')->willReturn(1);
        $missionFromDb->method('getName')->willReturn('Test Mission');
        $missionFromDb->method('getCountry')->willReturn($country);
        $missionFromDb->method('getAgents')->willReturn($this->createMock(\Doctrine\Common\Collections\Collection::class));
        $missionFromDb->method('getDanger')->willReturn(DangerLevel::High);

        $agent1 = $this->createMock(Agent::class);
        $agent1->method('getId')->willReturn(1);
        $agent1->method('getCodeName')->willReturn('Agent001');

        $agent2 = $this->createMock(Agent::class);
        $agent2->method('getId')->willReturn(2);
        $agent2->method('getCodeName')->willReturn('Agent002');

        $message = new MissionCreatedMessage($mission);

        $this->missionRepository->method('find')
            ->with(1)
            ->willReturn($missionFromDb);

        $this->agentRepository->method('findByCountry')
            ->with(1)
            ->willReturn([$agent1, $agent2]);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(Message::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->handler->__invoke($message);

        // Assert - Si on arrive ici sans exception, c'est un succès
        $this->assertTrue(true);
    }

    public function testInvokeWithNonExistentMission(): void
    {
        // Arrange
        $mission = $this->createMock(Mission::class);
        $mission->method('getId')->willReturn(999);

        $message = new MissionCreatedMessage($mission);

        $this->missionRepository->method('find')
            ->with(999)
            ->willReturn(null);

        // Assert & Act
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Mission not found in database');

        $this->handler->__invoke($message);
    }

    public function testInvokeWithMissionWithoutCountry(): void
    {
        // Arrange
        $mission = $this->createMock(Mission::class);
        $mission->method('getId')->willReturn(1);

        $missionFromDb = $this->createMock(Mission::class);
        $missionFromDb->method('getId')->willReturn(1);
        $missionFromDb->method('getCountry')->willReturn(null); // Pas de pays

        $message = new MissionCreatedMessage($mission);

        $this->missionRepository->method('find')
            ->with(1)
            ->willReturn($missionFromDb);

        $this->entityManager->expects($this->never())
            ->method('persist');

        $this->entityManager->expects($this->never())
            ->method('flush');

        // Act
        $this->handler->__invoke($message);

        // Assert - Aucune exception, mais aucun message créé
        $this->assertTrue(true);
    }

    public function testInvokeExcludesParticipatingAgents(): void
    {
        // Arrange
        $country = $this->createMock(Country::class);
        $country->method('getId')->willReturn(1);

        $participatingAgent = $this->createMock(Agent::class);
        $participatingAgent->method('getId')->willReturn(1);

        $mission = $this->createMock(Mission::class);
        $mission->method('getId')->willReturn(1);

        $missionFromDb = $this->createMock(Mission::class);
        $missionFromDb->method('getId')->willReturn(1);
        $missionFromDb->method('getName')->willReturn('Test Mission');
        $missionFromDb->method('getCountry')->willReturn($country);
        $missionFromDb->method('getAgents')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$participatingAgent]));
        $missionFromDb->method('getDanger')->willReturn(DangerLevel::High);

        $agentInCountry = $this->createMock(Agent::class);
        $agentInCountry->method('getId')->willReturn(1); // Même ID que l'agent participant
        $agentInCountry->method('getCodeName')->willReturn('Agent001');

        $otherAgent = $this->createMock(Agent::class);
        $otherAgent->method('getId')->willReturn(2);
        $otherAgent->method('getCodeName')->willReturn('Agent002');

        $message = new MissionCreatedMessage($mission);

        $this->missionRepository->method('find')
            ->with(1)
            ->willReturn($missionFromDb);

        $this->agentRepository->method('findByCountry')
            ->with(1)
            ->willReturn([$agentInCountry, $otherAgent]);

        // Seul l'agent qui ne participe pas doit recevoir un message
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Message::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->handler->__invoke($message);

        // Assert
        $this->assertTrue(true);
    }
}
