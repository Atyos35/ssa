<?php

namespace App\Application\Handler\Command;

use App\Application\Command\CommandInterface;
use App\Application\Command\ResendVerificationCommand;
use App\Application\Handler\CommandHandlerInterface;
use App\Domain\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
// Supprimé: use Symfony\Component\Uid\Uuid;

class ResendVerificationHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
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
        // Supprimé: $token = Uuid::v4()->toRfc4122();
        $token = bin2hex(random_bytes(32)); // Alternative plus simple
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationExpiresAt(new \DateTimeImmutable('+24 hours'));
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // TODO: Envoyer l'email (injecter le service EmailVerificationService)
        // $this->emailVerificationService->sendVerificationEmail($user, $token);
    }
} 