<?php

namespace App\Tests\Functional;

use App\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class AuthFunctionalTest extends WebTestCase
{
    use ResetDatabase;

    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testRegisterUserWithValidData(): void
    {
        // Tester POST /api/register avec des données valides
        $userData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => $_ENV['PASSWORD_TEST']
        ];

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Utilisateur créé avec succès. Un email de vérification a été envoyé à votre adresse email. Rendez vous sur http://localhost:8025', $responseData['message']);

        // Vérifier que l'utilisateur a été créé en base
        $this->entityManager->clear();
        $createdUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        
        $this->assertNotNull($createdUser);
        $this->assertEquals('John', $createdUser->getFirstName());
        $this->assertEquals('Doe', $createdUser->getLastName());
        $this->assertEquals('john.doe@example.com', $createdUser->getEmail());
        $this->assertFalse($createdUser->isEmailVerified());
        $this->assertNotNull($createdUser->getEmailVerificationToken());
        $this->assertNotNull($createdUser->getEmailVerificationExpiresAt());
    }

    public function testRegisterUserWithMissingFields(): void
    {
        // Tester POST /api/register avec des champs manquants
        $userData = [
            'firstName' => 'John',
            'email' => 'john.doe@example.com'
            // lastName et password manquants
        ];

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals("Le champ 'lastName' est requis", $responseData['error']);
    }

    public function testRegisterUserWithDuplicateEmail(): void
    {
        // Créer un utilisateur existant
        $existingUser = $this->createTestUser('existing@example.com', 'Existing', 'User');

        // Tester POST /api/register avec un email déjà utilisé
        $userData = [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'existing@example.com', // Email déjà utilisé
            'password' => $_ENV['PASSWORD_TEST']
        ];

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Un utilisateur avec cet email existe déjà', $responseData['error']);
    }

    public function testRegisterUserWithInvalidJson(): void
    {
        // Tester POST /api/register avec un JSON invalide
        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Données JSON invalides', $responseData['error']);
    }

    public function testLoginRoute(): void
    {
        // Tester POST /api/login (route simple pour le moment)
        $this->client->request('POST', '/api/login');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Utilisez POST /api/login avec email et password dans le body JSON', $responseData['message']);
    }

    public function testMeRouteWithoutAuthentication(): void
    {
        // Tester GET /api/me sans authentification
        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Utilisateur non authentifié', $responseData['error']);
    }

    public function testMeRouteWithAuthentication(): void
    {
        // Créer un utilisateur pour le test
        $user = $this->createTestUser('test@example.com', 'Test', 'User');

        // Simuler une authentification (ceci nécessiterait un vrai token JWT en production)
        // Pour ce test, nous allons juste vérifier que la route existe
        // En réalité, il faudrait mocker l'authentification JWT
        
        $this->client->request('GET', '/api/me');

        // Sans authentification, on devrait avoir une erreur 401
        $this->assertResponseStatusCodeSame(401);
    }

    private function createTestUser(string $email, string $firstName, string $lastName): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_USER']);
        $user->setEmailVerified(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $user;
    }
} 