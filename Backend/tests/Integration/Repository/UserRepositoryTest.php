<?php

namespace App\Tests\Integration\Repository;

use App\Domain\Entity\User;
use App\Infrastructure\Persistence\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class UserRepositoryTest extends KernelTestCase
{
    use ResetDatabase;

    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
    }

    public function testFindByEmail(): void
    {
        // Arrange - Créer des données de test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed_password');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmailVerified(false);
        $user->setEmailVerificationToken('token123');
        $user->setEmailVerificationExpiresAt(new \DateTimeImmutable('+1 hour'));
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Act
        $foundUser = $this->userRepository->findByEmail('test@example.com');
        
        // Assert
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals('test@example.com', $foundUser->getEmail());
        $this->assertEquals('John', $foundUser->getFirstName());
        $this->assertEquals('Doe', $foundUser->getLastName());
    }

    public function testFindByEmailNotFound(): void
    {
        // Act
        $foundUser = $this->userRepository->findByEmail('nonexistent@example.com');
        
        // Assert
        $this->assertNull($foundUser);
    }

    public function testFindByVerificationToken(): void
    {
        // Arrange
        $token = 'verification_token_123';
        $user = new User();
        $user->setEmail('verify@example.com');
        $user->setPassword('hashed_password');
        $user->setFirstName('Jane');
        $user->setLastName('Smith');
        $user->setEmailVerified(false);
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationExpiresAt(new \DateTimeImmutable('+1 hour'));
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Act
        $foundUser = $this->userRepository->findByVerificationToken($token);
        
        // Assert
        $this->assertInstanceOf(User::class, $foundUser);
        $this->assertEquals($token, $foundUser->getEmailVerificationToken());
        $this->assertEquals('verify@example.com', $foundUser->getEmail());
    }

    public function testEmailExists(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('existing@example.com');
        $user->setPassword('hashed_password');
        $user->setFirstName('Bob');
        $user->setLastName('Wilson');
        $user->setEmailVerified(true);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Act & Assert
        $this->assertTrue($this->userRepository->emailExists('existing@example.com'));
        $this->assertFalse($this->userRepository->emailExists('nonexistent@example.com'));
    }

    public function testFindUnverifiedUsers(): void
    {
        // Arrange
        $verifiedUser = new User();
        $verifiedUser->setEmail('verified@example.com');
        $verifiedUser->setPassword('hashed_password');
        $verifiedUser->setFirstName('Verified');
        $verifiedUser->setLastName('User');
        $verifiedUser->setEmailVerified(true);
        
        $unverifiedUser = new User();
        $unverifiedUser->setEmail('unverified@example.com');
        $unverifiedUser->setPassword('hashed_password');
        $unverifiedUser->setFirstName('Unverified');
        $unverifiedUser->setLastName('User');
        $unverifiedUser->setEmailVerified(false);
        $unverifiedUser->setEmailVerificationToken('token456');
        $unverifiedUser->setEmailVerificationExpiresAt(new \DateTimeImmutable('+1 hour'));
        
        $this->entityManager->persist($verifiedUser);
        $this->entityManager->persist($unverifiedUser);
        $this->entityManager->flush();

        // Act
        $unverifiedUsers = $this->userRepository->findUnverifiedUsers();
        
        // Assert
        $this->assertIsArray($unverifiedUsers);
        $this->assertGreaterThan(0, count($unverifiedUsers));
        
        // Vérifier que tous les utilisateurs retournés ne sont pas vérifiés
        foreach ($unverifiedUsers as $user) {
            $this->assertFalse($user->isEmailVerified());
        }
    }

    public function testFindVerifiedUsers(): void
    {
        // Arrange
        $verifiedUser1 = new User();
        $verifiedUser1->setEmail('verified1@example.com');
        $verifiedUser1->setPassword('hashed_password');
        $verifiedUser1->setFirstName('Alice');
        $verifiedUser1->setLastName('Verified');
        $verifiedUser1->setEmailVerified(true);
        
        $verifiedUser2 = new User();
        $verifiedUser2->setEmail('verified2@example.com');
        $verifiedUser2->setPassword('hashed_password');
        $verifiedUser2->setFirstName('Charlie');
        $verifiedUser2->setLastName('Verified');
        $verifiedUser2->setEmailVerified(true);
        
        $unverifiedUser = new User();
        $unverifiedUser->setEmail('unverified2@example.com');
        $unverifiedUser->setPassword('hashed_password');
        $unverifiedUser->setFirstName('David');
        $unverifiedUser->setLastName('Unverified');
        $unverifiedUser->setEmailVerified(false);
        
        $this->entityManager->persist($verifiedUser1);
        $this->entityManager->persist($verifiedUser2);
        $this->entityManager->persist($unverifiedUser);
        $this->entityManager->flush();

        // Act
        $verifiedUsers = $this->userRepository->findVerifiedUsers();
        
        // Assert
        $this->assertIsArray($verifiedUsers);
        $this->assertGreaterThanOrEqual(2, count($verifiedUsers));
        
        // Vérifier que tous les utilisateurs retournés sont vérifiés
        foreach ($verifiedUsers as $user) {
            $this->assertTrue($user->isEmailVerified());
        }
    }

    public function testFindWithPagination(): void
    {
        // Arrange - Créer plusieurs utilisateurs
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setEmail("user{$i}@example.com");
            $user->setPassword('hashed_password');
            $user->setFirstName("User{$i}");
            $user->setLastName('Test');
            $user->setEmailVerified(true);
            
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();

        // Act - Page 1, limite 3
        $users = $this->userRepository->findWithPagination(1, 3);
        
        // Assert
        $this->assertIsArray($users);
        $this->assertLessThanOrEqual(3, count($users));
        $this->assertGreaterThan(0, count($users));
    }
}
