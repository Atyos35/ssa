<?php

namespace App\Application\Security\Processor;

use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Metadata\Operation;
use App\Domain\Entity\Agent;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AgentPasswordHashProcessor implements ProcessorInterface
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;
    private EmailVerificationService $emailVerificationService;

    public function __construct(
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $passwordHasher,
        EmailVerificationService $emailVerificationService
    ) {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
        $this->emailVerificationService = $emailVerificationService;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof Agent && !empty($data->getPassword()) && !str_starts_with($data->getPassword(), '$2y$')) {
            $data->setPassword($this->passwordHasher->hashPassword($data, $data->getPassword()));
        }
        
        $this->em->persist($data);
        $this->em->flush();
        
        if ($data instanceof Agent) {
            $token = bin2hex(random_bytes(32)); // Alternative plus simple
            $data->setEmailVerificationToken($token);
            $data->setEmailVerificationExpiresAt(new \DateTimeImmutable('+24 hours'));
            $data->setEmailVerified(false);
            
            $this->em->flush();
            
            $this->emailVerificationService->sendVerificationEmail($data, $token);
        }
        
        return $data;
    }
}