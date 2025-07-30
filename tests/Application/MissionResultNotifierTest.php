<?php

namespace App\Tests\Application;

use App\Application\MissionResultNotifier;
use App\Entity\Agent;
use App\Entity\Country;
use App\Entity\Mission;
use App\Entity\MissionResult;
use App\Entity\MissionStatus;
use App\Entity\DangerLevel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class MissionResultNotifierTest extends TestCase
{
    public function testCreateMissionResultForSuccessMission()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        
        // Création des entités
        $country = $this->createMock(Country::class);
        $country->method('getName')->willReturn('Russie');

        $agent1 = $this->createMock(Agent::class);
        $agent2 = $this->createMock(Agent::class);
        $agents = new ArrayCollection([$agent1, $agent2]);

        $mission = $this->createMock(Mission::class);
        $mission->method('getStatus')->willReturn(MissionStatus::Success);
        $mission->method('getMissionResult')->willReturn(null);
        $mission->method('getName')->willReturn('Opération GoldenEye');
        $mission->method('getCountry')->willReturn($country);
        $mission->method('getDanger')->willReturn(DangerLevel::High);
        $mission->method('getAgents')->willReturn($agents);

        // On attend que persist soit appelé pour le MissionResult
        $em->expects($this->once())->method('persist')->with($this->callback(function ($missionResult) use ($mission) {
            return $missionResult instanceof MissionResult 
                && $missionResult->getStatus() === MissionStatus::Success
                && $missionResult->getMission() === $mission
                && strpos($missionResult->getSummary(), 'Opération GoldenEye') !== false
                && strpos($missionResult->getSummary(), 'Russie') !== false
                && strpos($missionResult->getSummary(), 'High') !== false
                && strpos($missionResult->getSummary(), '2 agent(s)') !== false
                && strpos($missionResult->getSummary(), 'succès') !== false;
        }));
        $em->expects($this->once())->method('flush');

        // Exécution
        $notifier = new MissionResultNotifier($em);
        $notifier->createMissionResult($mission);
    }

    public function testCreateMissionResultForFailureMission()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        
        // Création des entités
        $country = $this->createMock(Country::class);
        $country->method('getName')->willReturn('Chine');

        $agent1 = $this->createMock(Agent::class);
        $agents = new ArrayCollection([$agent1]);

        $mission = $this->createMock(Mission::class);
        $mission->method('getStatus')->willReturn(MissionStatus::Failure);
        $mission->method('getMissionResult')->willReturn(null);
        $mission->method('getName')->willReturn('Opération Dragon');
        $mission->method('getCountry')->willReturn($country);
        $mission->method('getDanger')->willReturn(DangerLevel::Medium);
        $mission->method('getAgents')->willReturn($agents);

        // On attend que persist soit appelé pour le MissionResult
        $em->expects($this->once())->method('persist')->with($this->callback(function ($missionResult) use ($mission) {
            return $missionResult instanceof MissionResult 
                && $missionResult->getStatus() === MissionStatus::Failure
                && $missionResult->getMission() === $mission
                && strpos($missionResult->getSummary(), 'Opération Dragon') !== false
                && strpos($missionResult->getSummary(), 'Chine') !== false
                && strpos($missionResult->getSummary(), 'Medium') !== false
                && strpos($missionResult->getSummary(), '1 agent(s)') !== false
                && strpos($missionResult->getSummary(), 'échouée') !== false;
        }));
        $em->expects($this->once())->method('flush');

        // Exécution
        $notifier = new MissionResultNotifier($em);
        $notifier->createMissionResult($mission);
    }

    public function testCreateMissionResultDoesNothingForNonTerminatedMission()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        
        $mission = $this->createMock(Mission::class);
        $mission->method('getStatus')->willReturn(MissionStatus::InProgress);
        $mission->method('getMissionResult')->willReturn(null);

        // On attend qu'aucune opération de base de données ne soit effectuée
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        // Exécution
        $notifier = new MissionResultNotifier($em);
        $notifier->createMissionResult($mission);
    }

    public function testCreateMissionResultDoesNothingWhenResultAlreadyExists()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        
        $existingResult = $this->createMock(MissionResult::class);
        
        $mission = $this->createMock(Mission::class);
        $mission->method('getStatus')->willReturn(MissionStatus::Success);
        $mission->method('getMissionResult')->willReturn($existingResult);

        // On attend qu'aucune opération de base de données ne soit effectuée
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');

        // Exécution
        $notifier = new MissionResultNotifier($em);
        $notifier->createMissionResult($mission);
    }

    public function testCreateMissionResultHandlesNullCountry()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        
        $agent1 = $this->createMock(Agent::class);
        $agents = new ArrayCollection([$agent1]);

        $mission = $this->createMock(Mission::class);
        $mission->method('getStatus')->willReturn(MissionStatus::Success);
        $mission->method('getMissionResult')->willReturn(null);
        $mission->method('getName')->willReturn('Opération Test');
        $mission->method('getCountry')->willReturn(null);
        $mission->method('getDanger')->willReturn(DangerLevel::Low);
        $mission->method('getAgents')->willReturn($agents);

        // On attend que persist soit appelé pour le MissionResult
        $em->expects($this->once())->method('persist')->with($this->callback(function ($missionResult) use ($mission) {
            return $missionResult instanceof MissionResult 
                && $missionResult->getStatus() === MissionStatus::Success
                && $missionResult->getMission() === $mission
                && strpos($missionResult->getSummary(), 'Pays inconnu') !== false;
        }));
        $em->expects($this->once())->method('flush');

        // Exécution
        $notifier = new MissionResultNotifier($em);
        $notifier->createMissionResult($mission);
    }
} 