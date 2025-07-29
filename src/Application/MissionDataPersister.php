<?php

namespace App\Application;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Mission;
use App\Entity\Agent;
use Doctrine\ORM\EntityManagerInterface;

class MissionDataPersister implements ProcessorInterface
{
    private EntityManagerInterface $em;
    private MissionStartNotifier $notifier;

    public function __construct(EntityManagerInterface $em, MissionStartNotifier $notifier)
    {
        $this->em = $em;
        $this->notifier = $notifier;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof Mission) {
            $missionCountry = $data->getCountry();
            foreach ($data->getAgents() as $agent) {
                $agent = $this->em->getRepository(Agent::class)->find($agent->getId());
                $agentCountry = $agent?->getInfiltratedCountry();
                if (!$agentCountry || !$missionCountry || $agentCountry->getId() !== $missionCountry->getId()) {
                    throw new \DomainException("L'agent ne peut pas participer à cette mission car il n'est pas infiltré dans le pays de la mission.");
                }
            }
        }

        // On notifie les autres agents
        $this->notifier->notifyAgentsOnMissionStart($data);

        $this->em->persist($data);
        $this->em->flush();
        
        return $data;
    }
}