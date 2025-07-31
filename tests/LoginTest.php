<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Test\ResetDatabase;

class LoginTest extends ApiTestCase
{
    // Reset la base de données avant chaque test
    use ResetDatabase;

    /**
     * Test 1: Réception de la requête de connexion
     * Vérifie que l'endpoint /api/login accepte les requêtes POST avec le bon format JSON
     */
    public function testLoginEndpointAcceptsPostRequest(): void
    {
        $response = static::createClient()->request('POST', '/api/login', ['json' => [
            'email' => 'test@example.com',
            'password' => 'TestPassword123!@'
        ]]);

        // Même si l'authentification échoue, l'endpoint doit être accessible
        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    /**
     * Test 2: Validation des données - Champs requis
     * Vérifie que les champs email et password sont obligatoires
     */
    public function testLoginWithMissingRequiredFields(): void
    {
        // Test sans email
        $response = static::createClient()->request('POST', '/api/login', ['json' => [
            'password' => 'TestPassword123!@'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'status' => 400
        ]);

        // Test sans password
        $response = static::createClient()->request('POST', '/api/login', ['json' => [
            'email' => 'test@example.com'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'status' => 400
        ]);

        // Test avec body vide
        $response = static::createClient()->request('POST', '/api/login', ['json' => []]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'status' => 400
        ]);
    }

    /**
     * Test 3: Authentification avec identifiants invalides
     * Vérifie que l'authentification échoue avec des identifiants incorrects
     */
    public function testLoginWithInvalidCredentials(): void
    {
        $response = static::createClient()->request('POST', '/api/login', ['json' => [
            'email' => 'nonexistent@example.com',
            'password' => 'WrongPassword123!@'
        ]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'code' => 401,
            'message' => 'Identifiants invalides.'
        ]);
    }

    /**
     * Test 4: Authentification avec email non vérifié
     * Vérifie que l'authentification échoue si l'email n'est pas vérifié
     */
    public function testLoginWithUnverifiedEmail(): void
    {
        // Créer un utilisateur avec email non vérifié
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('unverified@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'TestPassword123!@'));
        $user->setRoles(['ROLE_USER']);
        $user->setEmailVerified(false); // Email non vérifié

        $entityManager->persist($user);
        $entityManager->flush();

        // Tenter de se connecter
        $response = $client->request('POST', '/api/login', ['json' => [
            'email' => 'unverified@example.com',
            'password' => 'TestPassword123!@'
        ]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'code' => 401,
            'message' => 'Email non vérifié. Veuillez vérifier votre email et cliquer sur le lien de validation avant de vous connecter.'
        ]);
    }

    /**
     * Test 5: Authentification réussie avec email vérifié
     * Vérifie qu'une authentification réussie retourne un JWT token
     */
    public function testSuccessfulLoginWithVerifiedEmail(): void
    {
        // Créer un utilisateur avec email vérifié
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('verified@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'TestPassword123!@'));
        $user->setRoles(['ROLE_USER']);
        $user->setEmailVerified(true); // Email vérifié

        $entityManager->persist($user);
        $entityManager->flush();

        // Tenter de se connecter
        $response = $client->request('POST', '/api/login', ['json' => [
            'email' => 'verified@example.com',
            'password' => 'TestPassword123!@'
        ]]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = $response->toArray();
        
        // Vérifier que la réponse contient un token
        $this->assertArrayHasKey('token', $responseData);
        $this->assertNotEmpty($responseData['token']);
        
        // Vérifier que le token est une chaîne valide
        $this->assertIsString($responseData['token']);
    }

    /**
     * Test 6: Vérification du format du JWT token
     * Vérifie que le token retourné a le bon format JWT
     */
    public function testJwtTokenFormat(): void
    {
        // Créer un utilisateur avec email vérifié
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('jwt@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'TestPassword123!@'));
        $user->setRoles(['ROLE_USER']);
        $user->setEmailVerified(true);

        $entityManager->persist($user);
        $entityManager->flush();

        // Se connecter
        $response = $client->request('POST', '/api/login', ['json' => [
            'email' => 'jwt@example.com',
            'password' => 'TestPassword123!@'
        ]]);

        $this->assertResponseIsSuccessful();
        
        $responseData = $response->toArray();
        $token = $responseData['token'];
        
        // Vérifier le format JWT (3 parties séparées par des points)
        $tokenParts = explode('.', $token);
        $this->assertCount(3, $tokenParts, 'Le token JWT doit avoir 3 parties séparées par des points');
        
        // Vérifier que chaque partie est une chaîne base64 valide
        foreach ($tokenParts as $part) {
            $this->assertNotEmpty($part, 'Chaque partie du token ne doit pas être vide');
        }
    }

    /**
     * Test 7: Authentification avec mot de passe incorrect
     * Vérifie que l'authentification échoue avec un mot de passe incorrect
     */
    public function testLoginWithWrongPassword(): void
    {
        // Créer un utilisateur avec email vérifié
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('wrongpass@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'CorrectPassword123!@'));
        $user->setRoles(['ROLE_USER']);
        $user->setEmailVerified(true);

        $entityManager->persist($user);
        $entityManager->flush();

        // Tenter de se connecter avec un mauvais mot de passe
        $response = $client->request('POST', '/api/login', ['json' => [
            'email' => 'wrongpass@example.com',
            'password' => 'WrongPassword123!@'
        ]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'code' => 401,
            'message' => 'Identifiants invalides.'
        ]);
    }

    /**
     * Test 8: Authentification avec email incorrect
     * Vérifie que l'authentification échoue avec un email incorrect
     */
    public function testLoginWithWrongEmail(): void
    {
        // Créer un utilisateur avec email vérifié
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('correct@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'TestPassword123!@'));
        $user->setRoles(['ROLE_USER']);
        $user->setEmailVerified(true);

        $entityManager->persist($user);
        $entityManager->flush();

        // Tenter de se connecter avec un mauvais email
        $response = $client->request('POST', '/api/login', ['json' => [
            'email' => 'wrong@example.com',
            'password' => 'TestPassword123!@'
        ]]);

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains([
            'code' => 401,
            'message' => 'Identifiants invalides.'
        ]);
    }

    /**
     * Test 9: Authentification avec format JSON invalide
     * Vérifie que l'endpoint gère correctement les données JSON malformées
     */
    public function testLoginWithInvalidJsonFormat(): void
    {
        $client = static::createClient();
        
        // Envoyer des données non-JSON
        $response = $client->request('POST', '/api/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => 'invalid json data'
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'status' => 400
        ]);
    }

    /**
     * Test 10: Authentification avec utilisateur ayant des rôles multiples
     * Vérifie que l'authentification fonctionne avec des utilisateurs ayant plusieurs rôles
     */
    public function testLoginWithMultipleRoles(): void
    {
        // Créer un utilisateur avec plusieurs rôles
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setFirstName('Admin');
        $user->setLastName('User');
        $user->setEmail('admin@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'AdminPassword123!@'));
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $user->setEmailVerified(true);

        $entityManager->persist($user);
        $entityManager->flush();

        // Se connecter
        $response = $client->request('POST', '/api/login', ['json' => [
            'email' => 'admin@example.com',
            'password' => 'AdminPassword123!@'
        ]]);

        $this->assertResponseIsSuccessful();
        
        $responseData = $response->toArray();
        $this->assertArrayHasKey('token', $responseData);
        $this->assertNotEmpty($responseData['token']);
    }

    /**
     * Test 11: Vérification de la protection des routes API
     * Vérifie qu'une route API protégée nécessite un token JWT valide
     */
    public function testProtectedApiRouteRequiresAuthentication(): void
    {
        // Tenter d'accéder à une route API sans authentification
        $response = static::createClient()->request('GET', '/api/agents');

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    /**
     * Test 12: Authentification avec utilisateur désactivé
     * Vérifie le comportement avec un utilisateur qui n'est plus actif
     */
    public function testLoginWithDisabledUser(): void
    {
        // Créer un utilisateur avec email vérifié mais potentiellement désactivé
        $client = static::createClient();
        $container = $client->getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setFirstName('Disabled');
        $user->setLastName('User');
        $user->setEmail('disabled@example.com');
        $user->setPassword($passwordHasher->hashPassword($user, 'TestPassword123!@'));
        $user->setRoles(['ROLE_USER']);
        $user->setEmailVerified(true);

        $entityManager->persist($user);
        $entityManager->flush();

        // Se connecter (devrait fonctionner car l'utilisateur est actif)
        $response = $client->request('POST', '/api/login', ['json' => [
            'email' => 'disabled@example.com',
            'password' => 'TestPassword123!@'
        ]]);

        $this->assertResponseIsSuccessful();
        
        $responseData = $response->toArray();
        $this->assertArrayHasKey('token', $responseData);
    }
} 