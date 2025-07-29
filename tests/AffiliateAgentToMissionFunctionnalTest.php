<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Agent;
use App\Entity\Mission;
use App\Entity\Country;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

// Test fonctionnel pour l'affiliation d'un agent à une mission
class AffiliateAgentToMissionFunctionnalTest extends ApiTestCase
{
    // Client API Platform pour les requêtes HTTP
    private ?\ApiPlatform\Symfony\Bundle\Test\Client $client = null;

    // Purge la base avant chaque test
    protected function setUp(): void
    {
        $this->client = static::createClient();
        parent::setUp();
        $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $purger = new ORMPurger($em);
        $purger->setPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE);
        $purger->purge();
    }

    // Vérifie qu'un agent infiltré peut être ajouté à une mission
    public function testAgentCanBeAddedToMissionIfInfiltrated(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // Création d'un pays
        $country = new Country();
        $country->setName('Testland');
        $country->setDanger(\App\Entity\DangerLevel::Low);
        $country->setNumberOfAgents(1);
        $em->persist($country);

        // Création d'un agent infiltré dans le pays
        $agent = new Agent();
        $agent->setFirstName('John');
        $agent->setLastName('Doe');
        $agent->setCodeName('JD');
        $agent->setYearsOfExperience(5);
        $agent->setStatus(\App\Entity\AgentStatus::Available);
        $agent->setEnrolementDate(new \DateTimeImmutable());
        $agent->setEmail('john.doe_' . uniqid() . '@test.com');
        $agent->setPassword('test');
        $agent->setInfiltratedCountry($country);
        $em->persist($agent);

        // Création d'une mission dans le même pays
        $mission = new Mission();
        $mission->setName('Mission 1');
        $mission->setDanger(\App\Entity\DangerLevel::Low);
        $mission->setStatus(\App\Entity\MissionStatus::Success);
        $mission->setDescription('Desc');
        $mission->setObjectives('Obj');
        $mission->setStartDate(new \DateTimeImmutable());
        $mission->setCountry($country);
        $em->persist($mission);

        $em->flush();

        // Ajout de l'agent à la mission via l'API
        $this->client->request('PATCH', '/api/missions/'.$mission->getId(), [
            'json' => [
                'agents' => [
                    '/api/agents/'.$agent->getId()
                ]
            ],
            'headers' => ['Content-Type' => 'application/merge-patch+json']
        ]);

        // Vérifie que la requête est un succès
        $this->assertResponseIsSuccessful();
    }

    // Vérifie qu'un agent non infiltré ne peut pas être ajouté à une mission
    public function testAgentCannotBeAddedToMissionIfNotInfiltrated(): void
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // Création de deux pays
        $country1 = new Country();
        $country1->setName('A');
        $country1->setDanger(\App\Entity\DangerLevel::Low);
        $country1->setNumberOfAgents(1);
        $em->persist($country1);
        $country2 = new Country();
        $country2->setName('B');
        $country2->setDanger(\App\Entity\DangerLevel::Low);
        $country2->setNumberOfAgents(1);
        $em->persist($country2);

        // Création d'un agent infiltré dans le mauvais pays
        $agent = new Agent();
        $agent->setFirstName('Jane');
        $agent->setLastName('Smith');
        $agent->setCodeName('JS');
        $agent->setYearsOfExperience(3);
        $agent->setStatus(\App\Entity\AgentStatus::Available);
        $agent->setEnrolementDate(new \DateTimeImmutable());
        $agent->setEmail('jane.smith_' . uniqid() . '@test.com');
        $agent->setPassword('test');
        $agent->setInfiltratedCountry($country2);
        $em->persist($agent);

        // Création d'une mission dans le pays 1
        $mission = new Mission();
        $mission->setName('Mission 2');
        $mission->setDanger(\App\Entity\DangerLevel::Low);
        $mission->setStatus(\App\Entity\MissionStatus::Success);
        $mission->setDescription('Desc');
        $mission->setObjectives('Obj');
        $mission->setStartDate(new \DateTimeImmutable());
        $mission->setCountry($country1);
        $em->persist($mission);

        $em->flush();

        // Tente d'ajouter l'agent à la mission via l'API
        $this->client->request('PATCH', '/api/missions/'.$mission->getId(), [
            'json' => [
                'agents' => [
                    '/api/agents/'.$agent->getId()
                ]
            ],
            'headers' => ['Content-Type' => 'application/merge-patch+json']
        ]);

        // Vérifie qu'une erreur 500 est retournée (DomainException attendue)
        $this->assertResponseStatusCodeSame(500);
    }
}