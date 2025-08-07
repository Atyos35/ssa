<?php

namespace App\Application\Handler\Command;

use App\Application\Command\CommandInterface;
use App\Application\Command\CreateMissionCommand;
use App\Application\Handler\CommandHandlerInterface;
use App\Domain\Entity\Agent;
use App\Domain\Entity\Country;
use App\Domain\Entity\Mission;
use App\Domain\Service\MissionCreationService;
use App\Domain\Service\MissionValidationService;
use Doctrine\ORM\EntityManagerInterface;

class CreateMissionHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MissionCreationService $missionCreationService,
        private readonly MissionValidationService $missionValidationService
    ) {}

    public function handle(CommandInterface $command): void
    {
        if (!$command instanceof CreateMissionCommand) {
            throw new \InvalidArgumentException('Expected CreateMissionCommand');
        }

        // Récupérer le pays
        $country = $this->entityManager->getRepository(Country::class)->find($command->countryId);
        if (!$country) {
            throw new \DomainException('Country not found');
        }

        // Créer la mission
        $mission = new Mission();
        $mission->setName($command->name);
        $mission->setDescription($command->description);
        $mission->setObjectives($command->objectives);
        $mission->setDanger($command->danger);
        $mission->setStatus($command->status);
        $mission->setStartDate($command->startDate);
        $mission->setEndDate($command->endDate);
        $mission->setCountry($country);

        // Ajouter les agents
        foreach ($command->agentIds as $agentId) {
            $agent = $this->entityManager->getRepository(Agent::class)->find($agentId);
            if (!$agent) {
                throw new \DomainException("Agent with id $agentId not found");
            }
            $mission->addAgent($agent);
        }

        // Valider les agents
        $this->missionValidationService->validateMissionAgents($mission);

        // Créer la mission
        $this->missionCreationService->handleMissionCreation($mission);
    }
} 