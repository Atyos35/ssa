<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Agent;
use App\Entity\AgentStatus;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;

class AgentProcessFunctionalTest extends ApiTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine')->getManager();
        // On nettoie la base de test
        $this->em->createQuery('DELETE FROM App\\Entity\\Country c')->execute();
        $this->em->createQuery('DELETE FROM App\\Entity\\Message m')->execute();
        $this->em->createQuery('DELETE FROM App\\Entity\\Agent a')->execute();
    }

    public function testAgentDeathNotifiesOtherAgents(): void
    {
        // Création de deux agents vivants
        $agent1 = new Agent();
        $agent1->setCodeName('Alpha');
        $agent1->setYearsOfExperience(5);
        $agent1->setStatus(AgentStatus::Available);
        $agent1->setEnrolementDate(new \DateTimeImmutable('-2 years'));
        $agent1->setFirstName('Jean');
        $agent1->setLastName('Dupont');
        $agent1->setEmail('alpha@example.com');
        $agent1->setPassword('password123');
        $this->em->persist($agent1);

        $agent2 = new Agent();
        $agent2->setCodeName('Bravo');
        $agent2->setYearsOfExperience(3);
        $agent2->setStatus(AgentStatus::Available);
        $agent2->setEnrolementDate(new \DateTimeImmutable('-1 year'));
        $agent2->setFirstName('Marie');
        $agent2->setLastName('Durand');
        $agent2->setEmail('bravo@example.com');
        $agent2->setPassword('password456');
        $this->em->persist($agent2);

        $this->em->flush();

        // On passe agent1 au statut 'Killed in Action' via l'API
        static::createClient()->request('PATCH', '/api/agents/'.$agent1->getId(), [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json'
            ],
            'json' => [
                'status' => AgentStatus::KilledInAction->value
            ]
        ]);

        // On vérifie que agent2 a reçu un message
        $messages = $this->em->getRepository(Message::class)->findBy(['recipient' => $agent2]);
        $this->assertNotEmpty($messages, 'Agent2 doit avoir reçu au moins un message');
        $this->assertStringContainsString('Alpha', $messages[0]->getBody());
    }
} 