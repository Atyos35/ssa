<?php

namespace App\Application;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Agent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// Processor API Platform qui hash le mot de passe d'un agent lors d'un POST
class AgentPasswordHashProcessor implements ProcessorInterface
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    // Injection des dépendances Doctrine et du hasher Symfony
    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    // Méthode appelée lors d'un POST sur un agent
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        // Si le mot de passe est en clair, on le hash avant de persister
        if ($data instanceof Agent && !empty($data->getPassword()) && !str_starts_with($data->getPassword(), '$2y$')) {
            $data->setPassword($this->passwordHasher->hashPassword($data, $data->getPassword()));
        }
        // Persistance de l'agent
        $this->em->persist($data);
        $this->em->flush();
        return $data;
    }
}