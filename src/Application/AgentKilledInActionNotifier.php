<?php

namespace App\Application;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Agent;
use Doctrine\ORM\EntityManagerInterface;

// Processor API Platform qui notifie tous les agents lors du passage d'un agent au statut 'Killed in Action'
class AgentKilledInActionNotifier implements ProcessorInterface
{
    private EntityManagerInterface $em;
    private AgentDeathNotifier $notifier;

    // Injection des dépendances Doctrine et du service métier de notification
    public function __construct(EntityManagerInterface $em, AgentDeathNotifier $notifier)
    {
        $this->em = $em;
        $this->notifier = $notifier;
    }

    // Méthode appelée lors d'un PATCH sur un agent
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        // Si l'agent passe au statut "Killed in Action", on notifie tous les autres agents
        if ($data instanceof Agent && $data->getStatus()?->value === 'Killed in Action') {
            $this->notifier->notifyAllAgentsOnDeath($data);
        }
        // Persistance de l'agent modifié
        $this->em->persist($data);
        $this->em->flush();
        return $data;
    }
}