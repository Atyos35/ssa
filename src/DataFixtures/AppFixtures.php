<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Agent;
use App\Entity\Country;
use App\Entity\Mission;
use App\Entity\Message;
use App\Entity\MissionResult;
use App\Entity\DangerLevel;
use App\Entity\MissionStatus;
use App\Entity\AgentStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        // --- Création des pays ---
        $countries = [];
        $countryData = [
            ['name' => 'Royaume-Uni', 'danger' => DangerLevel::Medium, 'numberOfAgents' => 2],
            ['name' => 'États-Unis', 'danger' => DangerLevel::High, 'numberOfAgents' => 2],
            ['name' => 'Russie', 'danger' => DangerLevel::Critical, 'numberOfAgents' => 1],
            ['name' => 'France', 'danger' => DangerLevel::Low, 'numberOfAgents' => 1],
            ['name' => 'Égypte', 'danger' => DangerLevel::Low, 'numberOfAgents' => 1],
        ];
        foreach ($countryData as $data) {
            $country = new Country();
            $country->setName($data['name']);
            $country->setDanger($data['danger']);
            $country->setNumberOfAgents($data['numberOfAgents']);
            $manager->persist($country);
            $countries[] = $country;
        }

        // --- Création des agents ---
        $agents = [];
        $agentData = [
            [
                'firstName' => 'James', 'lastName' => 'Bond', 'codeName' => '007',
                'yearsOfExperience' => 20, 'status' => AgentStatus::OnMission,
                'enrolementDate' => new \DateTimeImmutable('2000-01-01'),
                'email' => 'james.bond@mi6.uk', 'password' => 'martini',
                'country' => $countries[0], // Royaume-Uni
            ],
            [
                'firstName' => 'Jason', 'lastName' => 'Bourne', 'codeName' => 'Delta One',
                'yearsOfExperience' => 15, 'status' => AgentStatus::Available,
                'enrolementDate' => new \DateTimeImmutable('2005-06-15'),
                'email' => 'jason.bourne@cia.gov', 'password' => 'treadstone',
                'country' => $countries[1], // États-Unis
            ],
            [
                'firstName' => 'Ethan', 'lastName' => 'Hunt', 'codeName' => 'Ghost',
                'yearsOfExperience' => 18, 'status' => AgentStatus::OnMission,
                'enrolementDate' => new \DateTimeImmutable('2002-09-10'),
                'email' => 'ethan.hunt@imf.int', 'password' => 'impossible',
                'country' => $countries[3], // France
            ],
            [
                'firstName' => 'OSS', 'lastName' => '117', 'codeName' => 'Hubert Bonisseur de La Bath',
                'yearsOfExperience' => 12, 'status' => AgentStatus::Retired,
                'enrolementDate' => new \DateTimeImmutable('2010-03-20'),
                'email' => 'oss.117@dgse.fr', 'password' => 'rio',
                'country' => $countries[4], // Égypte
            ],
            [
                'firstName' => 'Alec', 'lastName' => 'Trevelyan', 'codeName' => '006',
                'yearsOfExperience' => 17, 'status' => AgentStatus::KilledInAction,
                'enrolementDate' => new \DateTimeImmutable('1998-11-11'),
                'email' => 'alec.trevelyan@mi6.uk', 'password' => 'janus',
                'country' => $countries[2], // Russie
            ],
        ];
        foreach ($agentData as $data) {
            $agent = new Agent();
            $agent->setFirstName($data['firstName']);
            $agent->setLastName($data['lastName']);
            $agent->setCodeName($data['codeName']);
            $agent->setYearsOfExperience($data['yearsOfExperience']);
            $agent->setStatus($data['status']);
            $agent->setEnrolementDate($data['enrolementDate']);
            $agent->setEmail($data['email']);
            $agent->setRoles(['ROLE_AGENT']);
            // Hashage du mot de passe
            $agent->setPassword($this->hasher->hashPassword($agent, $data['password']));
            $agent->setInfiltratedCountry($data['country']);
            $manager->persist($agent);
            $agents[] = $agent;
        }

        // --- Définition des mentors ---
        $agents[0]->setMentor($agents[4]);
        $agents[1]->setMentor($agents[0]);
        $agents[2]->setMentor($agents[0]);
        $agents[3]->setMentor($agents[0]);
        $agents[4]->setMentor($agents[0]);

        // --- Définition des chefs ---
        $countries[0]->setCellLeader($agents[0]);nd
        $countries[1]->setCellLeader($agents[1]);rne
        $countries[2]->setCellLeader($agents[4]);an
        $countries[3]->setCellLeader($agents[2]);
        $countries[4]->setCellLeader($agents[3]);

        // --- Création des missions ---
        $missions = [];
        $missionData = [
            [
                'name' => 'Opération GoldenEye',
                'danger' => DangerLevel::Critical,
                'status' => MissionStatus::Success,
                'description' => 'Empêcher l’utilisation de l’arme satellite GoldenEye.',
                'objectives' => 'Infiltrer la base, neutraliser Janus, désactiver GoldenEye.',
                'startDate' => new \DateTimeImmutable('2024-01-10'),
                'endDate' => new \DateTimeImmutable('2024-01-20'),
                'country' => $countries[2],
                'agents' => [$agents[0], $agents[4]],
            ],
            [
                'name' => 'Treadstone',
                'danger' => DangerLevel::High,
                'status' => MissionStatus::Failure,
                'description' => 'Éliminer les traces du programme Treadstone.',
                'objectives' => 'Effacer les données, neutraliser les témoins.',
                'startDate' => new \DateTimeImmutable('2023-05-01'),
                'endDate' => new \DateTimeImmutable('2023-05-15'),
                'country' => $countries[1],
                'agents' => [$agents[1]],
            ],
            [
                'name' => 'Mission Fantôme',
                'danger' => DangerLevel::Medium,
                'status' => MissionStatus::Success,
                'description' => 'Opération secrète en France.',
                'objectives' => 'Surveillance, infiltration, extraction.',
                'startDate' => new \DateTimeImmutable('2024-03-01'),
                'endDate' => null,
                'country' => $countries[3],
                'agents' => [$agents[2]],
            ],
            [
                'name' => 'Paix en Égypte',
                'danger' => DangerLevel::High,
                'status' => MissionStatus::Success,
                'description' => 'Confortez les positions de la France, instaurez la paix en Égypte, sécurisez le Proche-Orient.',
                'objectives' => 'Négociation, diplomatie, sécurisation.',
                'startDate' => new \DateTimeImmutable('2024-04-01'),
                'endDate' => null,
                'country' => $countries[4],
                'agents' => [$agents[3]],
            ],
        ];
        foreach ($missionData as $data) {
            $mission = new Mission();
            $mission->setName($data['name']);
            $mission->setDanger($data['danger']);
            $mission->setStatus($data['status']);
            $mission->setDescription($data['description']);
            $mission->setObjectives($data['objectives']);
            $mission->setStartDate($data['startDate']);
            $mission->setEndDate($data['endDate']);
            $mission->setCountry($data['country']);
            foreach ($data['agents'] as $agent) {
                // Check que l'agent est bien infiltré dans le pays de la mission
                if ($agent->getInfiltratedCountry() === $data['country']) {
                    $mission->getAgents()->add($agent);
                }
            }
            $manager->persist($mission);
            $missions[] = $mission;
        }

        // --- Création des résultats de mission ---
        $resultsData = [
            ['mission' => $missions[0], 'status' => MissionStatus::Success, 'summary' => 'GoldenEye désactivé, Janus neutralisé.'],
            ['mission' => $missions[1], 'status' => MissionStatus::Failure, 'summary' => 'Fuite de données, programme compromis.'],
            ['mission' => $missions[2], 'status' => MissionStatus::Success, 'summary' => 'Extraction réussie.'],
            ['mission' => $missions[3], 'status' => MissionStatus::Success, 'summary' => 'Stabilité régionale renforcée, mission diplomatique réussie.'],
        ];
        foreach ($resultsData as $data) {
            $result = new MissionResult();
            $result->setMission($data['mission']);
            $result->setStatus($data['status']);
            $result->setSummary($data['summary']);
            $manager->persist($result);
        }

        // --- Création de messages entre agents ---
        $messagesData = [
            ['title' => 'Briefing', 'body' => 'Mission à haut risque, soyez prudents.', 'by' => $agents[0]],
            ['title' => 'Contact', 'body' => 'Point de rendez-vous à Paris.', 'by' => $agents[2]],
            ['title' => 'Rapport', 'body' => 'Mission accomplie, extraction en cours.', 'by' => $agents[1]],
        ];
        foreach ($messagesData as $data) {
            $message = new Message();
            $message->setTitle($data['title']);
            $message->setBody($data['body']);
            $message->setBy($data['by']);
            $manager->persist($message);
        }

        $manager->flush();
    }
} 