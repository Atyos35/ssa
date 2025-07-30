<?php

namespace App\Application;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Mission;
use Doctrine\ORM\EntityManagerInterface;

class MissionResultProcessor implements ProcessorInterface
{
    private EntityManagerInterface $em;
    private MissionResultNotifier $notifier;

    public function __construct(EntityManagerInterface $em, MissionResultNotifier $notifier)
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
            
            error_log('Mission persisted and flushed');

            // Créer le résultat de mission si la mission est terminée
            $this->notifier->createMissionResult($data);
            
            error_log('createMissionResult called');

            return $data;
        }

        error_log('Data is not Mission instance: ' . gettype($data));
        throw new \InvalidArgumentException('Data must be an instance of Mission');
    }
} 