<?php

namespace App\Tests\Application;

use App\Application\MissionResultProcessor;
use App\Application\MissionResultNotifier;
use App\Entity\Mission;
use App\Entity\MissionStatus;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ApiPlatform\Metadata\Operation;

class MissionResultProcessorTest extends TestCase
{
    public function testProcessWithMissionData()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $notifier = $this->createMock(MissionResultNotifier::class);
        $mission = $this->createMock(Mission::class);
        $operation = $this->createMock(Operation::class);

        // On attend que la mission soit persistée et que le notifier soit appelé
        $em->expects($this->once())->method('persist')->with($mission);
        $em->expects($this->once())->method('flush');
        $notifier->expects($this->once())->method('createMissionResult')->with($mission);

        // Exécution
        $processor = new MissionResultProcessor($em, $notifier);
        $result = $processor->process($mission, $operation);

        // Vérification du retour
        $this->assertSame($mission, $result);
    }

    public function testProcessWithNonMissionDataThrowsException()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $notifier = $this->createMock(MissionResultNotifier::class);
        $operation = $this->createMock(Operation::class);

        // On attend qu'aucune opération de base de données ne soit effectuée
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');
        $notifier->expects($this->never())->method('createMissionResult');

        // Exécution et vérification de l'exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be an instance of Mission');

        $processor = new MissionResultProcessor($em, $notifier);
        $processor->process('invalid data', $operation);
    }

    public function testProcessWithNullDataThrowsException()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $notifier = $this->createMock(MissionResultNotifier::class);
        $operation = $this->createMock(Operation::class);

        // On attend qu'aucune opération de base de données ne soit effectuée
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');
        $notifier->expects($this->never())->method('createMissionResult');

        // Exécution et vérification de l'exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be an instance of Mission');

        $processor = new MissionResultProcessor($em, $notifier);
        $processor->process(null, $operation);
    }
} 