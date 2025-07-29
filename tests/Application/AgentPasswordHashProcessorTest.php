<?php

namespace App\Tests\Application;

use App\Application\AgentPasswordHashProcessor;
use App\Entity\Agent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use PHPUnit\Framework\TestCase;

class AgentPasswordHashProcessorTest extends TestCase
{
    public function testProcessHashesPasswordIfNotAlreadyHashed()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $agent = $this->createMock(Agent::class);
        $agent->method('getPassword')->willReturn('plainPassword');
        $hasher->expects($this->once())->method('hashPassword')->with($agent, 'plainPassword')->willReturn('hashedPassword');
        $agent->expects($this->once())->method('setPassword')->with('hashedPassword');
        $em->expects($this->once())->method('persist')->with($agent);
        $em->expects($this->once())->method('flush');

        // Exécution
        $processor = new AgentPasswordHashProcessor($em, $hasher);
        $processor->process($agent, $this->createStub(\ApiPlatform\Metadata\Operation::class));
    }

    public function testProcessDoesNotHashIfAlreadyHashed()
    {
        // Préparation des mocks
        $em = $this->createMock(EntityManagerInterface::class);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $agent = $this->createMock(Agent::class);
        $agent->method('getPassword')->willReturn('$2y$alreadyHashed');
        $hasher->expects($this->never())->method('hashPassword');
        $agent->expects($this->never())->method('setPassword');
        $em->expects($this->once())->method('persist')->with($agent);
        $em->expects($this->once())->method('flush');

        // Exécution
        $processor = new AgentPasswordHashProcessor($em, $hasher);
        $processor->process($agent, $this->createStub(\ApiPlatform\Metadata\Operation::class));
    }
}