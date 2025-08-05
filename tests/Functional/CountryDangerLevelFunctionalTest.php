<?php

namespace App\Tests\Functional;

use App\Entity\Country;
use App\Entity\DangerLevel;
use App\Factory\CountryFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CountryDangerLevelFunctionalTest extends WebTestCase
{
    use ResetDatabase, Factories;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCountryDangerLevelUpdatesViaApi(): void
    {
        // Créer un pays sans niveau de danger
        $country = CountryFactory::createOne([
            'name' => 'France',
            'danger' => null,
        ]);

        // Vérifier que le pays n'a pas de niveau de danger initialement
        $this->assertNull($country->getDanger());

        // Créer une mission via l'API
        $missionData = [
            'name' => 'Mission Infiltration',
            'danger' => 'High',
            'status' => 'InProgress',
            'country' => '/api/countries/' . $country->getId(),
            'description' => 'Mission d\'infiltration dans un réseau terroriste',
            'objectives' => 'Infiltration et collecte d\'informations sensibles',
            'startDate' => '2024-01-15',
        ];

        $this->client->request(
            'POST',
            '/api/missions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode($missionData)
        );

        // Vérifier que la requête a réussi
        $this->assertResponseIsSuccessful();

        // Récupérer le pays depuis la base de données pour vérifier la mise à jour
        $updatedCountry = $this->entityManager->getRepository(Country::class)->find($country->getId());

        // Le niveau de danger du pays devrait maintenant être High
        $this->assertEquals(DangerLevel::High, $updatedCountry->getDanger());
    }

    public function testCountryDangerLevelUpdatesWithMultipleMissionsViaApi(): void
    {
        // Créer un pays sans niveau de danger
        $country = CountryFactory::createOne([
            'name' => 'France',
            'danger' => null,
        ]);

        // Créer une première mission via l'API
        $missionData1 = [
            'name' => 'Mission Surveillance',
            'danger' => 'Medium',
            'status' => 'InProgress',
            'country' => '/api/countries/' . $country->getId(),
            'description' => 'Mission de surveillance',
            'objectives' => 'Surveiller les activités suspectes',
            'startDate' => '2024-01-15',
        ];

        $this->client->request(
            'POST',
            '/api/missions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode($missionData1)
        );

        $this->assertResponseIsSuccessful();

        // Récupérer le pays depuis la base de données
        $updatedCountry = $this->entityManager->getRepository(Country::class)->find($country->getId());

        // Le niveau de danger du pays devrait être Medium
        $this->assertEquals(DangerLevel::Medium, $updatedCountry->getDanger());

        // Créer une deuxième mission avec niveau de danger Critical
        $missionData2 = [
            'name' => 'Mission Extraction',
            'danger' => 'Critical',
            'status' => 'InProgress',
            'country' => '/api/countries/' . $country->getId(),
            'description' => 'Mission d\'extraction d\'urgence',
            'objectives' => 'Extraire des agents en danger',
            'startDate' => '2024-01-15',
        ];

        $this->client->request(
            'POST',
            '/api/missions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode($missionData2)
        );

        $this->assertResponseIsSuccessful();

        // Récupérer le pays depuis la base de données
        $updatedCountry = $this->entityManager->getRepository(Country::class)->find($country->getId());

        // Le niveau de danger du pays devrait maintenant être Critical (le plus élevé)
        $this->assertEquals(DangerLevel::Critical, $updatedCountry->getDanger());
    }

    public function testCountryDangerLevelStaysHighestWhenLowerMissionIsCreatedViaApi(): void
    {
        // Créer un pays sans niveau de danger
        $country = CountryFactory::createOne([
            'name' => 'France',
            'danger' => null,
        ]);

        // Créer une mission avec niveau de danger High
        $missionData1 = [
            'name' => 'Mission Infiltration',
            'danger' => 'High',
            'status' => 'InProgress',
            'country' => '/api/countries/' . $country->getId(),
            'description' => 'Mission d\'infiltration',
            'objectives' => 'Infiltration et collecte d\'informations',
            'startDate' => '2024-01-15',
        ];

        $this->client->request(
            'POST',
            '/api/missions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode($missionData1)
        );

        $this->assertResponseIsSuccessful();

        // Récupérer le pays depuis la base de données
        $updatedCountry = $this->entityManager->getRepository(Country::class)->find($country->getId());

        // Le niveau de danger du pays devrait être High
        $this->assertEquals(DangerLevel::High, $updatedCountry->getDanger());

        // Créer une deuxième mission avec niveau de danger Medium (plus faible)
        $missionData2 = [
            'name' => 'Mission Surveillance',
            'danger' => 'Medium',
            'status' => 'InProgress',
            'country' => '/api/countries/' . $country->getId(),
            'description' => 'Mission de surveillance',
            'objectives' => 'Surveiller les activités',
            'startDate' => '2024-01-15',
        ];

        $this->client->request(
            'POST',
            '/api/missions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/ld+json'],
            json_encode($missionData2)
        );

        $this->assertResponseIsSuccessful();

        // Récupérer le pays depuis la base de données
        $updatedCountry = $this->entityManager->getRepository(Country::class)->find($country->getId());

        // Le niveau de danger du pays devrait rester High (le plus élevé)
        $this->assertEquals(DangerLevel::High, $updatedCountry->getDanger());
    }
} 