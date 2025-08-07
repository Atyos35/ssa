<?php

namespace App\Tests\Functional;

use App\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class RegistrationFunctionalTest extends WebTestCase
{
    // Reset la base de données avant chaque test
    use ResetDatabase;

    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Test 1: Réception de la requête d'inscription
     * Vérifie que l'endpoint /api/register accepte les requêtes POST avec le bon format JSON
     */
    public function testRegisterEndpointAcceptsPostRequest(): void
    {
        $response = $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

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
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals("Le champ 'firstName' est requis", $responseData['error']);

        // Test avec lastName manquant
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString("Le champ 'lastName' est requis", $responseData['error']);

        // Test avec email manquant
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString("Le champ 'email' est requis", $responseData['error']);

        // Test avec password manquant
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString("Le champ 'password' est requis", $responseData['error']);
    }

    /**
     * Test 3: Validation des données - Unicité de l'email
     * Vérifie que l'email est unique en base de données
     */
    public function testRegisterWithDuplicateEmail(): void
    {
        // Créer un premier utilisateur
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Essayer de créer un deuxième utilisateur avec le même email
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'Jane',
            'lastName' => 'Smith',
            'email' => 'john.doe@example.com',
            'password' => 'AnotherPass123!!'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Un utilisateur avec cet email existe déjà', $responseData['error']);
    }

    /**
     * Test 4: Validation des données - Contraintes sur le mot de passe
     * Vérifie que les contraintes de validation du mot de passe sont respectées
     */
    public function testRegisterWithInvalidPassword(): void
    {
        // Test avec mot de passe trop court (moins de 12 caractères)
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'Short1!'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringStartsWith('Erreurs de validation:', $responseData['error']);

        // Test avec mot de passe sans caractères spéciaux
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'MOTdepasse123'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringStartsWith('Erreurs de validation:', $responseData['error']);

        // Test avec mot de passe sans majuscules
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'motdepasse123!!'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringStartsWith('Erreurs de validation:', $responseData['error']);
    }

    /**
     * Test 5: Création de l'utilisateur - Inscription réussie
     * Vérifie qu'un utilisateur est créé avec succès avec les bonnes données
     */
    public function testSuccessfulRegistration(): void
    {
        $response = $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        // Vérifie le message de succès
        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('Utilisateur créé avec succès', $responseData['message']);

        // Vérifie que les données utilisateur sont retournées
        $this->assertArrayHasKey('user', $responseData);
        $this->assertEquals('John', $responseData['user']['firstName']);
        $this->assertEquals('Doe', $responseData['user']['lastName']);
        $this->assertEquals('john.doe@example.com', $responseData['user']['email']);
        $this->assertFalse($responseData['user']['emailVerified']);
        $this->assertArrayHasKey('id', $responseData['user']);

        // Vérifie que l'utilisateur est bien créé en base de données
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        
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
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Vérifie que le mot de passe en base est hashé (différent du mot de passe original)
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        
        $this->assertNotEquals('SecurePass123!!', $user->getPassword());
        $this->assertStringStartsWith('$2y$', $user->getPassword()); // Format bcrypt
    }

    /**
     * Test 7: Génération du token de vérification
     * Vérifie qu'un token de vérification d'email est généré
     */
    public function testEmailVerificationTokenIsGenerated(): void
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Vérifie que le token de vérification est généré
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        
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
        // Test avec un body vide
        $this->client->request('POST', '/api/register', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => ''
        ]);

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Données JSON invalides', $responseData['error']);

        // Test avec un JSON malformé
        $this->client->request('POST', '/api/register', [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => '{"firstName": "John", "lastName": "Doe", "email": "john.doe@example.com", "password": "SecurePass123!!"'
        ]);

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Données JSON invalides', $responseData['error']);
    }

    /**
     * Test 9: Rôles par défaut
     * Vérifie que les rôles par défaut sont correctement assignés
     */
    public function testDefaultRolesAreAssigned(): void
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(201);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    /**
     * Test 10: Rôles personnalisés
     * Vérifie que des rôles personnalisés peuvent être assignés
     */
    public function testCustomRolesCanBeAssigned(): void
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'Admin',
            'lastName' => 'User',
            'email' => 'admin@example.com',
            'password' => 'SecurePass123!!',
            'roles' => ['ROLE_ADMIN', 'ROLE_USER']
        ]));

        $this->assertResponseStatusCodeSame(201);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);
        
        $this->assertEquals(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    /**
     * Test 11: Statut email non vérifié par défaut
     * Vérifie que l'email est marqué comme non vérifié par défaut
     */
    public function testEmailIsNotVerifiedByDefault(): void
    {
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(201);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        
        $this->assertFalse($user->isEmailVerified());
    }

    /**
     * Test 12: Envoi d'email de vérification
     * Vérifie qu'un email de vérification est envoyé lors de l'inscription
     */
    public function testVerificationEmailIsSent(): void
    {
        // En mode test, le mailer est configuré sur null://null
        // Nous vérifions que le processus d'envoi d'email ne génère pas d'erreur
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Vérifier que le message de réponse indique qu'un email a été envoyé
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('email de vérification a été envoyé', $responseData['message']);
        
        // Vérifier que l'utilisateur a un token de vérification (indiquant que l'email aurait été envoyé)
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'john.doe@example.com']);
        $this->assertNotNull($user->getEmailVerificationToken());
    }

    /**
     * Test 13: Validation de l'entité - Contraintes de validation
     * Vérifie que les contraintes de validation de l'entité User sont respectées
     */
    public function testEntityValidationConstraints(): void
    {
        // Test avec email invalide
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'invalid-email',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringStartsWith('Erreurs de validation:', $responseData['error']);

        // Test avec firstName trop court
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'J',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringStartsWith('Erreurs de validation:', $responseData['error']);

        // Test avec lastName trop long
        $this->client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'firstName' => 'John',
            'lastName' => str_repeat('A', 51), // Plus de 50 caractères
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!!'
        ]));

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringStartsWith('Erreurs de validation:', $responseData['error']);
    }
}