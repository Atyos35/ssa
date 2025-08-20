<?php

namespace App\Application\Handler\Command;

use App\Application\Command\CommandInterface;
use App\Application\Command\RegisterUserCommand;
use App\Application\Handler\CommandHandlerInterface;
use App\Domain\Entity\User;
use App\Domain\Service\EmailVerificationService;
use App\Infrastructure\Persistence\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Uid\Uuid;

class RegisterUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly EmailVerificationService $emailService,
        private readonly UserRepository $userRepository
    ) {}

    public function handle(CommandInterface $command): void
    {
        if (!$command instanceof RegisterUserCommand) {
            throw new \InvalidArgumentException('Expected RegisterUserCommand');
        }

        // Vérifier si l'email existe déjà
        $existingUser = $this->userRepository->findByEmail($command->email);
        
        if ($existingUser) {
            throw new \DomainException('Un utilisateur avec cet email existe déjà');
        }

        // Créer un nouvel utilisateur
        $user = new User();
        $user->setFirstName($command->firstName);
        $user->setLastName($command->lastName);
        $user->setEmail($command->email);
        $user->setRoles($command->roles);
        $user->setEmailVerified(false); // Email non vérifié par défaut

        // Valider le mot de passe original avant de le hasher
        $user->setPassword($command->password); // Temporairement pour la validation
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new \DomainException('Erreurs de validation: ' . implode(', ', $errorMessages));
        }

        // Hasher le mot de passe après validation
        $hashedPassword = $this->passwordHasher->hashPassword($user, $command->password);
        $user->setPassword($hashedPassword);

        // Générer un token de vérification d'email
        $verificationToken = Uuid::v4()->toRfc4122();
        $user->setEmailVerificationToken($verificationToken);
        $user->setEmailVerificationExpiresAt(new \DateTimeImmutable('+24 hours'));

        // Sauvegarder l'utilisateur
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Envoyer l'email de vérification
        $this->emailService->sendVerificationEmail($user, $verificationToken);
    }
} 