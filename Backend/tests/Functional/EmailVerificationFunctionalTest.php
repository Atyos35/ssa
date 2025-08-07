<?php

namespace App\Tests\Functional;

use App\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class EmailVerificationFunctionalTest extends WebTestCase
{
    use ResetDatabase;

    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testVerifyEmailWithValidToken(): void
    {
        // Créer un utilisateur avec un token de vérification valide
        $user = $this->createTestUserWithValidToken();

        // Tester GET /api/verify-email/{token}
        $this->client->request('GET', '/api/verify-email/' . $user->getEmailVerificationToken());

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Email vérifié avec succès', $responseData['message']);

        // Vérifier que l'utilisateur a été marqué comme vérifié
        $this->entityManager->clear();
        $updatedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertTrue($updatedUser->isEmailVerified());
        $this->assertNull($updatedUser->getEmailVerificationToken());
        $this->assertNull($updatedUser->getEmailVerificationExpiresAt());
    }

    public function testVerifyEmailWithInvalidToken(): void
    {
        // Tester avec un token invalide
        $this->client->request('GET', '/api/verify-email/invalid-token');

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Token invalide', $responseData['error']);
    }

    public function testVerifyEmailWithExpiredToken(): void
    {
        // Créer un utilisateur avec un token expiré
        $user = $this->createTestUserWithExpiredToken();

        // Tester GET /api/verify-email/{token}
        $this->client->request('GET', '/api/verify-email/' . $user->getEmailVerificationToken());

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Token expiré', $responseData['error']);
    }

    public function testVerifyEmailAlreadyVerified(): void
    {
        // Créer un utilisateur déjà vérifié
        $user = $this->createTestUserAlreadyVerified();

        // Tester GET /api/verify-email/{token}
        $this->client->request('GET', '/api/verify-email/' . $user->getEmailVerificationToken());

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Email déjà vérifié', $responseData['error']);
    }

    public function testResendVerificationWithValidEmail(): void
    {
        // Créer un utilisateur non vérifié
        $user = $this->createTestUserNotVerified();

        // Tester POST /api/resend-verification
        $data = ['email' => $user->getEmail()];

        $this->client->request(
            'POST',
            '/api/resend-verification',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Email de vérification renvoyé', $responseData['message']);

        // Vérifier qu'un nouveau token a été généré
        $this->entityManager->clear();
        $updatedUser = $this->entityManager->getRepository(User::class)->find($user->getId());
        $this->assertNotNull($updatedUser->getEmailVerificationToken());
        $this->assertNotNull($updatedUser->getEmailVerificationExpiresAt());
        // Vérifier que l'expiration est dans le futur
        $this->assertGreaterThan(new \DateTimeImmutable(), $updatedUser->getEmailVerificationExpiresAt());
    }

    public function testResendVerificationWithInvalidEmail(): void
    {
        // Tester avec un email inexistant
        $data = ['email' => 'nonexistent@example.com'];

        $this->client->request(
            'POST',
            '/api/resend-verification',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Utilisateur non trouvé', $responseData['error']);
    }

    public function testResendVerificationWithAlreadyVerifiedEmail(): void
    {
        // Créer un utilisateur déjà vérifié
        $user = $this->createTestUserAlreadyVerified();

        // Tester POST /api/resend-verification
        $data = ['email' => $user->getEmail()];

        $this->client->request(
            'POST',
            '/api/resend-verification',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Email déjà vérifié', $responseData['error']);
    }

    public function testResendVerificationWithoutEmail(): void
    {
        // Tester sans email
        $data = [];

        $this->client->request(
            'POST',
            '/api/resend-verification',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Email requis', $responseData['error']);
    }

    private function createTestUserWithValidToken(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('password123');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmailVerified(false);
        $user->setEmailVerificationToken('valid-token-123');
        $user->setEmailVerificationExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }

    private function createTestUserWithExpiredToken(): User
    {
        $user = new User();
        $user->setEmail('expired@example.com');
        $user->setPassword('password123');
        $user->setFirstName('Jane');
        $user->setLastName('Smith');
        $user->setEmailVerified(false);
        $user->setEmailVerificationToken('expired-token-123');
        $user->setEmailVerificationExpiresAt(new \DateTimeImmutable('-1 hour'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }

    private function createTestUserAlreadyVerified(): User
    {
        $user = new User();
        $user->setEmail('verified@example.com');
        $user->setPassword('password123');
        $user->setFirstName('Bob');
        $user->setLastName('Wilson');
        $user->setEmailVerified(true);
        $user->setEmailVerificationToken('verified-token-123');
        $user->setEmailVerificationExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }

    private function createTestUserNotVerified(): User
    {
        $user = new User();
        $user->setEmail('notverified@example.com');
        $user->setPassword('password123');
        $user->setFirstName('Alice');
        $user->setLastName('Brown');
        $user->setEmailVerified(false);
        $user->setEmailVerificationToken('old-token-123');
        $user->setEmailVerificationExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }
} 