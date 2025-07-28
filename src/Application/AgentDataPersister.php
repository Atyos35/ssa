<?php

namespace App\Application;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Agent;
use Doctrine\ORM\EntityManagerInterface;

class AgentDataPersister implements ProcessorInterface
{
    private EntityManagerInterface $em;
    private AgentDeathNotifier $notifier;

    public function __construct(EntityManagerInterface $em, AgentDeathNotifier $notifier)
    {
        $this->em = $em;
        $this->notifier = $notifier;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        // On détecte le passage au statut 'Killed in Action'
        if ($data instanceof Agent && $data->getStatus()?->value === 'Killed in Action') {
            $this->notifier->notifyAllAgentsOnDeath($data);
        }
        $this->em->persist($data);
        $this->em->flush();
        return $data;
    }
} 