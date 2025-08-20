<?php

namespace App\Tests\Unit\Handler\Command;

use App\Application\Handler\Command\CreateAgentHandler;
use App\Application\Command\CreateAgentCommand;
use App\Application\Command\CommandInterface;
use App\Domain\Entity\Agent;
use App\Domain\Entity\AgentStatus;
use App\Domain\Entity\Country;
use App\Domain\Entity\DangerLevel;
use App\Infrastructure\Persistence\Repository\AgentRepository;
use App\Infrastructure\Persistence\Repository\CountryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use PHPUnit\Framework\TestCase;

class CreateAgentHandlerTest extends TestCase
{
    private CreateAgentHandler $handler;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private CountryRepository $countryRepository;
    private AgentRepository $agentRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->countryRepository = $this->createMock(CountryRepository::class);
        $this->agentRepository = $this->createMock(AgentRepository::class);
        
        $this->handler = new CreateAgentHandler(
            $this->entityManager,
            $this->passwordHasher,
            $this->countryRepository,
            $this->agentRepository
        );
    }

    public function testHandleWithValidCommand(): void
    {
        // Arrange
        $command = new CreateAgentCommand(
            'Agent001',
            'John',
            'Doe',
            'john.doe@test.com',
            'password123',
            5,
            AgentStatus::Available,
            new \DateTimeImmutable(),
            1,
            null
        );

        $country = new Country();
        $country->setName('Test Country');
        $country->setDanger(DangerLevel::Low);

        $this->countryRepository->method('find')
            ->with(1)
            ->willReturn($country);

        $this->passwordHasher->method('hashPassword')
            ->willReturn('hashed_password');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Agent::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        // Act
        $this->handler->handle($command);

        // Assert - Si on arrive ici sans exception, c'est un succès
        $this->assertTrue(true);
    }

    public function testHandleWithInvalidCommandType(): void
    {
        // Arrange
        $invalidCommand = $this->createMock(CommandInterface::class);
        
        // Assert & Act
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected CreateAgentCommand');
        
        $this->handler->handle($invalidCommand);
    }

    public function testHandleWithNonExistentCountry(): void
    {
        // Arrange
        $command = new CreateAgentCommand(
            'Agent001',
            'John',
            'Doe',
            'john.doe@test.com',
            'password123',
            5,
            AgentStatus::Available,
            new \DateTimeImmutable(),
            999,
            null
        );

        $this->countryRepository->method('find')
            ->with(999)
            ->willReturn(null);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Country not found');
        
        $this->handler->handle($command);
    }

    public function testHandleWithNonExistentMentor(): void
    {
        // Arrange
        $command = new CreateAgentCommand(
            'Agent001',
            'John',
            'Doe',
            'john.doe@test.com',
            'password123',
            5,
            AgentStatus::Available,
            new \DateTimeImmutable(),
            1,
            999
        );

        $country = new Country();
        $country->setName('Test Country');
        $country->setDanger(DangerLevel::Low);

        $this->countryRepository->method('find')
            ->with(1)
            ->willReturn($country);

        $this->agentRepository->method('find')
            ->with(999)
            ->willReturn(null);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Mentor not found');
        
        $this->handler->handle($command);
    }
}

