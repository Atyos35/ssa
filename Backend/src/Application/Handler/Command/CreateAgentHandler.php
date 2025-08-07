<?php

namespace App\Application\Handler\Command;

use App\Application\Command\CommandInterface;
use App\Application\Command\CreateAgentCommand;
use App\Application\Handler\CommandHandlerInterface;
use App\Domain\Entity\Agent;
use App\Domain\Entity\Country;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAgentHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {}

    public function handle(CommandInterface $command): void
    {
        if (!$command instanceof CreateAgentCommand) {
            throw new \InvalidArgumentException('Expected CreateAgentCommand');
        }

        // Créer l'agent
        $agent = new Agent();
        $agent->setCodeName($command->codeName);
        $agent->setFirstName($command->firstName);
        $agent->setLastName($command->lastName);
        $agent->setEmail($command->email);
        $agent->setYearsOfExperience($command->yearsOfExperience);
        $agent->setStatus($command->status);
        $agent->setEnrolementDate($command->enrolementDate);

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($agent, $command->password);
        $agent->setPassword($hashedPassword);

        // Définir le pays d'infiltration si fourni
        if ($command->infiltratedCountryId !== null) {
            $country = $this->entityManager->getRepository(Country::class)->find($command->infiltratedCountryId);
            if (!$country) {
                throw new \DomainException('Country not found');
            }
            $agent->setInfiltratedCountry($country);
        }

        // Définir le mentor si fourni
        if ($command->mentorId !== null) {
            $mentor = $this->entityManager->getRepository(Agent::class)->find($command->mentorId);
            if (!$mentor) {
                throw new \DomainException('Mentor not found');
            }
            $agent->setMentor($mentor);
        }

        // Persister l'agent
        $this->entityManager->persist($agent);
        $this->entityManager->flush();
    }
} 