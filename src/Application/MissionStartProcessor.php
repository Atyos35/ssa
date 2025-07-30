<?php

namespace App\Application;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Mission;
use Doctrine\ORM\EntityManagerInterface;

class MissionStartProcessor implements ProcessorInterface
{
    private EntityManagerInterface $em;
    private MissionStartNotifier $notifier;

    public function __construct(EntityManagerInterface $em, MissionStartNotifier $notifier)
    {
        $this->em = $em;
        $this->notifier = $notifier;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Mission
    {
        if ($data instanceof Mission) {
            // Persister la mission
            $this->em->persist($data);
            $this->em->flush();

            // Notifier les agents du pays (sauf ceux qui participent)
            $this->notifier->notifyAgentsOnMissionStart($data);

            return $data;
        }

        throw new \InvalidArgumentException('Data must be an instance of Mission');
    }
} 