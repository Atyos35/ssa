<?php

namespace App\Tests\Functional;

use App\Domain\Entity\Country;
use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionStatus;
use App\Domain\Entity\DangerLevel;
use App\Shared\Factory\CountryFactory;
use App\Shared\Factory\MissionFactory;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CountryDangerLevelFunctionalTest extends WebTestCase
{
    use ResetDatabase, Factories;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testCountryDangerLevelUpdatedAfterMissionCreation(): void
    {
        $client = static::createClient();
        
        // Crée un pays avec un niveau de danger initial
        $country = CountryFactory::createOne([
            'name' => 'France',
            'danger' => DangerLevel::Low,
            'numberOfAgents' => 5
        ]);
        $initialDangerLevel = $country->getDanger();
        
        // Crée une mission avec un niveau de danger plus élevé
        $missionData = [
            'name' => 'Mission Test Danger',
            'description' => 'Description de la mission test',
            'objectives' => 'Objectifs de la mission test',
            'danger' => DangerLevel::High->value,
            'status' => MissionStatus::InProgress->value,
            'startDate' => (new \DateTimeImmutable())->format('Y-m-d'),
            'country' => '/api/countries/' . $country->getId()
        ];

        $client->request('POST', '/api/missions', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode($missionData));

        $this->assertResponseIsSuccessful();
        
        // Vérifie que le niveau de danger du pays a été mis à jour
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $updatedCountry = CountryFactory::repository()->find($country->getId());
        $this->assertEquals(DangerLevel::High, $updatedCountry->getDanger());
    }

    public function testCountryDangerLevelUpdatedToMissionLevelWhenOnlyOneMission(): void
    {
        $client = static::createClient();
        
        // Crée un pays avec un niveau de danger élevé
        $country = CountryFactory::createOne([
            'name' => 'France',
            'danger' => DangerLevel::High,
            'numberOfAgents' => 5
        ]);
        
        // Crée une mission avec un niveau de danger plus faible
        $missionData = [
            'name' => 'Mission Test Danger Faible',
            'description' => 'Description de la mission test',
            'objectives' => 'Objectifs de la mission test',
            'danger' => DangerLevel::Low->value,
            'status' => MissionStatus::InProgress->value,
            'startDate' => (new \DateTimeImmutable())->format('Y-m-d'),
            'country' => '/api/countries/' . $country->getId()
        ];

        $client->request('POST', '/api/missions', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
        ], json_encode($missionData));

        $this->assertResponseIsSuccessful();
        
        // Vérifie que le niveau de danger du pays a été mis à jour au niveau de la mission
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $updatedCountry = CountryFactory::repository()->find($country->getId());
        $this->assertEquals(DangerLevel::Low, $updatedCountry->getDanger());
    }

    public function testCountryDangerLevelUpdatedAfterMissionStatusChange(): void
    {
        $client = static::createClient();
        
        // Crée un pays
        $country = CountryFactory::createOne([
            'name' => 'France',
            'danger' => DangerLevel::Low,
            'numberOfAgents' => 5
        ]);
        
        // Crée une mission avec un niveau de danger élevé
        $mission = MissionFactory::createOne([
            'name' => 'Mission Test Status Change',
            'description' => 'Description de la mission test',
            'objectives' => 'Objectifs de la mission test',
            'danger' => DangerLevel::Critical,
            'status' => MissionStatus::InProgress,
            'startDate' => new \DateTimeImmutable(),
            'country' => $country
        ]);
        
        // Vérifie que le niveau de danger du pays a été mis à jour
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $updatedCountry = CountryFactory::repository()->find($country->getId());
        $this->assertEquals(DangerLevel::Critical, $updatedCountry->getDanger());
        
        // Change le statut de la mission à Success
        $missionData = [
            'status' => MissionStatus::Success->value
        ];

        $client->request('PATCH', '/api/missions/' . $mission->getId(), [], [], [
            'CONTENT_TYPE' => 'application/merge-patch+json',
        ], json_encode($missionData));

        $this->assertResponseIsSuccessful();
        
        // Vérifie que le niveau de danger du pays est revenu au niveau par défaut
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $finalCountry = CountryFactory::repository()->find($country->getId());
        $this->assertEquals(DangerLevel::Low, $finalCountry->getDanger());
    }

    public function testCountryDangerLevelUpdatedWithHighestActiveMission(): void
    {
        $client = static::createClient();
        
        // Crée un pays
        $country = CountryFactory::createOne([
            'name' => 'France',
            'danger' => DangerLevel::Low,
            'numberOfAgents' => 5
        ]);
        
        // Crée plusieurs missions avec différents niveaux de danger
        MissionFactory::createOne([
            'name' => 'Mission Low',
            'description' => 'Mission de faible danger',
            'objectives' => 'Objectifs',
            'danger' => DangerLevel::Low,
            'status' => MissionStatus::InProgress,
            'startDate' => new \DateTimeImmutable(),
            'country' => $country
        ]);
        
        MissionFactory::createOne([
            'name' => 'Mission Medium',
            'description' => 'Mission de danger moyen',
            'objectives' => 'Objectifs',
            'danger' => DangerLevel::Medium,
            'status' => MissionStatus::InProgress,
            'startDate' => new \DateTimeImmutable(),
            'country' => $country
        ]);
        
        MissionFactory::createOne([
            'name' => 'Mission High',
            'description' => 'Mission de danger élevé',
            'objectives' => 'Objectifs',
            'danger' => DangerLevel::High,
            'status' => MissionStatus::InProgress,
            'startDate' => new \DateTimeImmutable(),
            'country' => $country
        ]);
        
        // Vérifie que le niveau de danger du pays correspond au plus élevé
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $updatedCountry = CountryFactory::repository()->find($country->getId());
        $this->assertEquals(DangerLevel::High, $updatedCountry->getDanger());
    }
} 