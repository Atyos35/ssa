<?php

namespace App\Application\Handler\Command;

use App\Application\Command\CommandInterface;
use App\Application\Command\UpdateMissionCommand;
use App\Application\Handler\CommandHandlerInterface;
use App\Domain\Entity\Agent;
use App\Domain\Entity\Country;
use App\Domain\Entity\Mission;
use App\Domain\Entity\MissionResult;
use App\Domain\Entity\MissionStatus;
use App\Domain\Service\MissionValidationService;
use App\Infrastructure\Persistence\Repository\MissionRepository;
use App\Infrastructure\Persistence\Repository\CountryRepository;
use App\Infrastructure\Persistence\Repository\AgentRepository;
use Doctrine\ORM\EntityManagerInterface;

class UpdateMissionHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MissionValidationService $missionValidationService,
        private readonly MissionRepository $missionRepository,
        private readonly CountryRepository $countryRepository,
        private readonly AgentRepository $agentRepository
    ) {}

    public function handle(CommandInterface $command): void
    {
        if (!$command instanceof UpdateMissionCommand) {
            throw new \InvalidArgumentException('Expected UpdateMissionCommand');
        }

        $mission = $this->missionRepository->find($command->missionId);
        if (!$mission) {
            throw new \DomainException('Mission not found');
        }

        if ($command->name !== null) {
            $mission->setName($command->name);
        }
        if ($command->description !== null) {
            $mission->setDescription($command->description);
        }
        if ($command->objectives !== null) {
            $mission->setObjectives($command->objectives);
        }
        if ($command->danger !== null) {
            $mission->setDanger($command->danger);
        }
        if ($command->startDate !== null) {
            $mission->setStartDate($command->startDate);
        }
        if ($command->endDate !== null) {
            $mission->setEndDate($command->endDate);
        }

        if ($command->countryId !== null) {
            $country = $this->countryRepository->find($command->countryId);
            if (!$country) {
                throw new \DomainException('Country not found');
            }
            $mission->setCountry($country);
        }

        if ($command->agentIds !== null) {
            $mission->getAgents()->clear();
            
            foreach ($command->agentIds as $agentId) {
                $agent = $this->agentRepository->find($agentId);
                if (!$agent) {
                    throw new \DomainException("Agent with id $agentId not found");
                }
                $mission->addAgent($agent);
            }
        }

        if ($command->status !== null) {
            $mission->setStatus($command->status);
            
            if (in_array($command->status, [MissionStatus::Success, MissionStatus::Failure])) {
                $this->handleMissionResult($mission, $command->missionResultSummary);
            }
        }

        $this->missionValidationService->validateMissionAgents($mission);

        $this->entityManager->persist($mission);
        $this->entityManager->flush();
    }

    private function handleMissionResult(Mission $mission, ?string $summary): void
    {
        $missionResult = $mission->getMissionResult();
        
        if (!$missionResult) {
            $missionResult = new MissionResult();
            $missionResult->setMission($mission);
            $missionResult->setStatus($mission->getStatus());
            $mission->setMissionResult($missionResult); // Mettre à jour la relation inverse
            $this->entityManager->persist($missionResult);
        } else {
            $missionResult->setStatus($mission->getStatus());
        }

        if ($summary !== null) {
            $missionResult->setSummary($summary);
        }

        if ($mission->getEndDate() === null) {
            $mission->setEndDate(new \DateTimeImmutable());
        }
    }
}