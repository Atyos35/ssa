<?php

namespace App\Application\Handler\Command;

use App\Application\Command\CommandInterface;
use App\Application\Command\ResendVerificationCommand;
use App\Application\Handler\CommandHandlerInterface;
use App\Domain\Entity\User;
use App\Domain\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

class ResendVerificationHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailVerificationService $emailVerificationService
    ) {}

    public function handle(CommandInterface $command): void
    {
        if (!$command instanceof ResendVerificationCommand) {
            throw new \InvalidArgumentException('Expected ResendVerificationCommand');
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $command->email
        ]);

        if (!$user) {
            throw new \DomainException('Utilisateur non trouvé');
        }

        if ($user->isEmailVerified()) {
            throw new \DomainException('Email déjà vérifié');
        }

        // Générer un nouveau token de vérification
        $token = Uuid::v4()->toRfc4122();
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationExpiresAt(new \DateTimeImmutable('+24 hours'));
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Envoyer l'email de vérification
        $this->emailVerificationService->sendVerificationEmail($user, $token);
    }
} 