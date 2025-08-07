<?php

namespace App\Application\Handler\Command;

use App\Application\Command\CommandInterface;
use App\Application\Command\VerifyEmailCommand;
use App\Application\Handler\CommandHandlerInterface;
use App\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class VerifyEmailHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function handle(CommandInterface $command): void
    {
        if (!$command instanceof VerifyEmailCommand) {
            throw new \InvalidArgumentException('Expected VerifyEmailCommand');
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'emailVerificationToken' => $command->token
        ]);

        if (!$user) {
            throw new \DomainException('Token invalide');
        }

        if ($user->getEmailVerificationExpiresAt() < new \DateTimeImmutable()) {
            throw new \DomainException('Token expiré');
        }

        if ($user->isEmailVerified()) {
            throw new \DomainException('Email déjà vérifié');
        }

        // Vérifier l'email
        $user->setEmailVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationExpiresAt(null);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
} 