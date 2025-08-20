<?php

namespace App\Tests\Unit\Handler\Command;

use App\Application\Handler\Command\RegisterUserHandler;
use App\Application\Command\RegisterUserCommand;
use App\Application\Command\CommandInterface;
use App\Domain\Entity\User;
use App\Domain\Service\EmailVerificationService;
use App\Infrastructure\Persistence\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use PHPUnit\Framework\TestCase;

class RegisterUserHandlerTest extends TestCase
{
    private RegisterUserHandler $handler;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;
    private EmailVerificationService $emailService;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->emailService = $this->createMock(EmailVerificationService::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        
        $this->handler = new RegisterUserHandler(
            $this->entityManager,
            $this->passwordHasher,
            $this->validator,
            $this->emailService,
            $this->userRepository
        );
    }

    public function testHandleWithValidCommand(): void
    {
        // Arrange
        $command = new RegisterUserCommand(
            'John',
            'Doe',
            'john.doe@test.com',
            'password123'
        );

        $this->userRepository->method('findByEmail')
            ->with('john.doe@test.com')
            ->willReturn(null);

        $this->passwordHasher->method('hashPassword')
            ->willReturn('hashed_password');

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->emailService->expects($this->once())
            ->method('sendVerificationEmail')
            ->with($this->isInstanceOf(User::class), $this->isType('string'));

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
        $this->expectExceptionMessage('Expected RegisterUserCommand');
        
        $this->handler->handle($invalidCommand);
    }

    public function testHandleWithExistingEmail(): void
    {
        // Arrange
        $command = new RegisterUserCommand(
            'John',
            'Doe',
            'existing@test.com',
            'password123'
        );

        $existingUser = $this->createMock(User::class);
        $this->userRepository->method('findByEmail')
            ->with('existing@test.com')
            ->willReturn($existingUser);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Un utilisateur avec cet email existe déjà');
        
        $this->handler->handle($command);
    }

    public function testHandleWithValidationErrors(): void
    {
        // Arrange
        $command = new RegisterUserCommand(
            'John',
            'Doe',
            'john.doe@test.com',
            'password123'
        );

        $this->userRepository->method('findByEmail')
            ->with('john.doe@test.com')
            ->willReturn(null);

        $this->passwordHasher->method('hashPassword')
            ->willReturn('hashed_password');

        // Simuler des erreurs de validation
        $violations = $this->createMock(ConstraintViolationList::class);
        $violations->method('count')->willReturn(1);
        $violations->method('getIterator')->willReturn(new \ArrayIterator([
            $this->createMock(\Symfony\Component\Validator\ConstraintViolationInterface::class)
        ]));

        $this->validator->method('validate')
            ->willReturn($violations);

        // Assert & Act
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Erreurs de validation:');
        
        $this->handler->handle($command);
    }

    public function testHandleGeneratesVerificationToken(): void
    {
        // Arrange
        $command = new RegisterUserCommand(
            'John',
            'Doe',
            'john.doe@test.com',
            'password123'
        );

        $this->userRepository->method('findByEmail')
            ->with('john.doe@test.com')
            ->willReturn(null);

        $this->passwordHasher->method('hashPassword')
            ->willReturn('hashed_password');

        $this->validator->method('validate')
            ->willReturn(new ConstraintViolationList());

        // Capture l'utilisateur persisté pour vérifier le token
        $persistedUser = null;
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($user) use (&$persistedUser) {
                $persistedUser = $user;
                return $user instanceof User;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->emailService->expects($this->once())
            ->method('sendVerificationEmail')
            ->with($this->isInstanceOf(User::class), $this->isType('string'));

        // Act
        $this->handler->handle($command);

        // Assert - Vérifier qu'un token de vérification a été généré
        $this->assertInstanceOf(User::class, $persistedUser);
        $this->assertNotNull($persistedUser->getEmailVerificationToken());
        $this->assertNotEmpty($persistedUser->getEmailVerificationToken());
    }
}
