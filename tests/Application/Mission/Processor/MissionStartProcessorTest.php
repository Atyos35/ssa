<?php

namespace App\Tests\Application\Mission\Processor;

use App\Application\Mission\Processor\MissionStartProcessor;
use App\Application\Mission\Notifier\MissionStartNotifier;
use App\Entity\Mission;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ApiPlatform\Metadata\Operation;

class MissionStartProcessorTest extends TestCase
{
    public function testProcessPersistsMissionAndNotifiesAgents()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $notifier = $this->createMock(MissionStartNotifier::class);
        $mission = $this->createMock(Mission::class);
        $operation = $this->createMock(Operation::class);

        // On attend que la mission soit persistée et que la notification soit envoyée
        $em->expects($this->once())->method('persist')->with($mission);
        $em->expects($this->once())->method('flush');
        $notifier->expects($this->once())->method('notifyAgentsOnMissionStart')->with($mission);

        // Exécution
        $processor = new MissionStartProcessor($em, $notifier);
        $result = $processor->process($mission, $operation);

        // Vérification du retour
        $this->assertSame($mission, $result);
    }

    public function testProcessThrowsExceptionForNonMissionData()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $notifier = $this->createMock(MissionStartNotifier::class);
        $operation = $this->createMock(Operation::class);

        // On attend qu'aucune opération de base de données ne soit effectuée
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');
        $notifier->expects($this->never())->method('notifyAgentsOnMissionStart');

        // On attend une exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be an instance of Mission');

        // Exécution avec des données invalides
        $processor = new MissionStartProcessor($em, $notifier);
        $processor->process('invalid data', $operation);
    }

    public function testProcessWithNullData()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $notifier = $this->createMock(MissionStartNotifier::class);
        $operation = $this->createMock(Operation::class);

        // On attend qu'aucune opération de base de données ne soit effectuée
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');
        $notifier->expects($this->never())->method('notifyAgentsOnMissionStart');

        // On attend une exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be an instance of Mission');

        // Exécution avec des données null
        $processor = new MissionStartProcessor($em, $notifier);
        $processor->process(null, $operation);
    }

    public function testProcessWithArrayData()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $notifier = $this->createMock(MissionStartNotifier::class);
        $operation = $this->createMock(Operation::class);

        // On attend qu'aucune opération de base de données ne soit effectuée
        $em->expects($this->never())->method('persist');
        $em->expects($this->never())->method('flush');
        $notifier->expects($this->never())->method('notifyAgentsOnMissionStart');

        // On attend une exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data must be an instance of Mission');

        // Exécution avec un tableau
        $processor = new MissionStartProcessor($em, $notifier);
        $processor->process(['name' => 'test'], $operation);
    }
} 