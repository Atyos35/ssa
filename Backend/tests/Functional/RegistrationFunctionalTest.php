<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Zenstruck\Foundry\Test\ResetDatabase;

class RegistrationFunctionalTest extends ApiTestCase
{
    // Reset la base de données avant chaque test
    use ResetDatabase;

    /**
     * Test 1: Réception de la requête d'inscription
     * Vérifie que l'endpoint /api/register accepte les requêtes POST avec le bon format JSON
     */
    public function testRegisterEndpointAcceptsPostRequest(): void
    {
        $response = static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]]);

        // Vérifie que la requête est acceptée (pas d'erreur 404 ou 405)
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    /**
     * Test 2: Validation des données - Champs requis
     * Vérifie que tous les champs requis sont présents et non vides
     */
    public function testRegisterWithMissingRequiredFields(): void
    {
        // Test avec firstName manquant
        static::createClient()->request('POST', '/api/register', ['json' => [
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => "Le champ 'firstName' est requis"
        ]);

        // Test avec lastName manquant
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => "Le champ 'lastName' est requis"
        ]);

        // Test avec email manquant
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => "Le champ 'email' est requis"
        ]);

        // Test avec password manquant
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => "Le champ 'password' est requis"
        ]);
    }

    /**
     * Test 3: Validation des données - Unicité de l'email
     * Vérifie que l'email est unique en base de données
     */
    public function testRegisterWithDuplicateEmail(): void
    {
        // Créer un premier utilisateur
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(201);

        // Essayer de créer un deuxième utilisateur avec le même email
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'john.doe@example.com',
            'password' => 'AnotherPass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertJsonContains([
            'error' => 'Un utilisateur avec cet email existe déjà'
        ]);
    }

    /**
     * Test 4: Validation des données - Contraintes sur le mot de passe
     * Vérifie que les contraintes de validation du mot de passe sont respectées
     */
    public function testRegisterWithInvalidPassword(): void
    {
        // Test avec mot de passe trop court (moins de 12 caractères)
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Short1!'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => 'Erreurs de validation'
        ]);

        // Test avec mot de passe sans caractères spéciaux
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MOTdepasse123'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => 'Erreurs de validation'
        ]);

        // Test avec mot de passe sans majuscules
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'motdepasse123!!'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => 'Erreurs de validation'
        ]);
    }

    /**
     * Test 5: Création de l'utilisateur - Inscription réussie
     * Vérifie qu'un utilisateur est créé avec succès avec les bonnes données
     */
    public function testSuccessfulRegistration(): void
    {
        $response = static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = $response->toArray();
        
        // Vérifie le message de succès
        $this->assertJsonContains([
            'message' => 'Utilisateur créé avec succès. Un email de vérification a été envoyé à votre adresse email.'
        ]);

        // Vérifie que les données utilisateur sont retournées
        $this->assertArrayHasKey('user', $responseData);
        $this->assertEquals('John', $responseData['user']['firstName']);
        $this->assertEquals('Doe', $responseData['user']['lastName']);
        $this->assertEquals('john.doe@example.com', $responseData['user']['email']);
        $this->assertFalse($responseData['user']['emailVerified']);
        $this->assertArrayHasKey('id', $responseData['user']);

        // Vérifie que l'utilisateur est bien créé en base de données
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        
        $this->assertNotNull($user);
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('john.doe@example.com', $user->getEmail());
        $this->assertFalse($user->isEmailVerified());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    /**
     * Test 6: Sécurisation du mot de passe - Vérification du hachage
     * Vérifie que le mot de passe est correctement hashé en base de données
     */
    public function testPasswordIsHashed(): void
    {
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(201);

        // Vérifie que le mot de passe en base est hashé (différent du mot de passe original)
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        
        $this->assertNotEquals('SecurePass123!!', $user->getPassword());
        $this->assertStringStartsWith('$2y$', $user->getPassword()); // Format bcrypt
    }

    /**
     * Test 7: Génération du token de vérification
     * Vérifie qu'un token de vérification d'email est généré
     */
    public function testEmailVerificationTokenIsGenerated(): void
    {
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(201);

        // Vérifie que le token de vérification est généré
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        
        $this->assertNotNull($user->getEmailVerificationToken());
        $this->assertNotNull($user->getEmailVerificationExpiresAt());
        
        // Vérifie que le token expire dans 24 heures
        $now = new \DateTimeImmutable();
        $expiresAt = $user->getEmailVerificationExpiresAt();
        $this->assertGreaterThan($now, $expiresAt);
        $this->assertLessThanOrEqual($now->modify('+25 hours'), $expiresAt);
    }

    /**
     * Test 8: Validation de l'entité - Données JSON invalides
     * Vérifie que les données JSON invalides sont rejetées
     */
    public function testRegisterWithInvalidJson(): void
    {
        $client = static::createClient();
        
        // Test avec un body vide
        $client->request('POST', '/api/register', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => ''
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => 'Données JSON invalides'
        ]);

        // Test avec un JSON malformé
        $client->request('POST', '/api/register', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{"firstName": "John", "lastName": "Doe", "email": "john.doe@example.com", "password": "SecurePass123!!"'
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => 'Données JSON invalides'
        ]);
    }

    /**
     * Test 9: Rôles par défaut
     * Vérifie que les rôles par défaut sont correctement assignés
     */
    public function testDefaultRolesAreAssigned(): void
    {
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(201);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    /**
     * Test 10: Rôles personnalisés
     * Vérifie que des rôles personnalisés peuvent être assignés
     */
    public function testCustomRolesCanBeAssigned(): void
    {
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'Admin',
            'lastName' => 'User',
            'email' => 'admin@example.com',
            'password' => 'SecurePass123!!',
            'roles' => ['ROLE_ADMIN', 'ROLE_USER']
        ]]);

        $this->assertResponseStatusCodeSame(201);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        
        $this->assertEquals(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    /**
     * Test 11: Statut email non vérifié par défaut
     * Vérifie que l'email est marqué comme non vérifié par défaut
     */
    public function testEmailIsNotVerifiedByDefault(): void
    {
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(201);

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        
        $this->assertFalse($user->isEmailVerified());
    }

    /**
     * Test 12: Validation des contraintes de l'entité
     * Vérifie que toutes les contraintes de validation de l'entité User sont respectées
     */
    public function testEntityValidationConstraints(): void
    {
        // Test avec email invalide
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'invalid-email',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => 'Erreurs de validation'
        ]);

        // Test avec firstName trop court
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'J',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => 'Erreurs de validation'
        ]);

        // Test avec lastName trop long
        static::createClient()->request('POST', '/api/register', ['json' => [
            'firstName' => 'John',
            'lastName' => str_repeat('A', 51), // Plus de 50 caractères
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertJsonContains([
            'error' => 'Erreurs de validation'
        ]);
    }
}