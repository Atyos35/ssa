<?php

namespace App\Tests\Unit\Handler\Command;

use App\Application\Handler\Command\VerifyEmailHandler;
use App\Application\Command\VerifyEmailCommand;
use App\Application\Command\CommandInterface;
use App\Domain\Entity\User;
use App\Infrastructure\Persistence\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class VerifyEmailHandlerTest extends TestCase
{
    private VerifyEmailHandler $handler;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        
        $this->handler = new VerifyEmailHandler(
            $this->entityManager,
            $this->userRepository
        );
    }

    public function testHandleWithValidCommand(): void
    {
        // Arrange
        $token = 'valid_token_123';
        $command = new VerifyEmailCommand($token);

        $user = $this->createMock(User::class);
        $user->method('isEmailVerified')->willReturn(false);
        $user->method('getEmailVerificationExpiresAt')->willReturn(new \DateTimeImmutable('+1 hour'));
        $user->expects($this->once())->method('setEmailVerified')->with(true);
        $user->expects($this->once())->method('setEmailVerificationToken')->with(null);
        $user->expects($this->once())->method('setEmailVerificationExpiresAt')->with(null);

        $this->userRepository->method('findByVerificationToken')
            ->with($token)
            ->willReturn($user);

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
        $this->expectExceptionMessage('Expected VerifyEmailCommand');
        
        $this->handler->handle($invalidCommand);
    }

    public function testHandleWithInvalidToken(): void
    {
        // Arrange
        $token = 'invalid_token';
        $command = new VerifyEmailCommand($token);

        $this->userRepository->method('findByVerificationToken')
            ->with($token)
            ->willReturn(null);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Token invalide');
        
        $this->handler->handle($command);
    }

    public function testHandleWithExpiredToken(): void
    {
        // Arrange
        $token = 'expired_token_123';
        $command = new VerifyEmailCommand($token);

        $user = $this->createMock(User::class);
        $user->method('isEmailVerified')->willReturn(false);
        $user->method('getEmailVerificationExpiresAt')->willReturn(new \DateTimeImmutable('-1 hour')); // Expiré

        $this->userRepository->method('findByVerificationToken')
            ->with($token)
            ->willReturn($user);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Token expiré');
        
        $this->handler->handle($command);
    }

    public function testHandleWithAlreadyVerifiedEmail(): void
    {
        // Arrange
        $token = 'valid_token_123';
        $command = new VerifyEmailCommand($token);

        $user = $this->createMock(User::class);
        $user->method('isEmailVerified')->willReturn(true); // Déjà vérifié
        $user->method('getEmailVerificationExpiresAt')->willReturn(new \DateTimeImmutable('+1 hour'));

        $this->userRepository->method('findByVerificationToken')
            ->with($token)
            ->willReturn($user);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Email déjà vérifié');
        
        $this->handler->handle($command);
    }
}
