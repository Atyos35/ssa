<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Uid\Uuid;
use App\Service\EmailVerificationService;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Obligé de générer cette route manuellement, je n'arrive pas à la générer automatiquement avec le firewall
        return new JsonResponse([
            'message' => 'Utilisez POST /api/login avec email et password dans le body JSON'
        ]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        EmailVerificationService $emailService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse([
                'error' => 'Données JSON invalides'
            ], 400);
        }

        // Vérifier les champs requis
        $requiredFields = ['firstName', 'lastName', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return new JsonResponse([
                    'error' => "Le champ '$field' est requis"
                ], 400);
            }
        }

        // Vérifier si l'email existe déjà
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse([
                'error' => 'Un utilisateur avec cet email existe déjà'
            ], 409);
        }

        // Créer un nouvel utilisateur
        $user = new User();
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setEmail($data['email']);
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);
        $user->setEmailVerified(false); // Email non vérifié par défaut

        // Valider le mot de passe original avant de le hasher
        $user->setPassword($data['password']); // Temporairement pour la validation
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse([
                'error' => 'Erreurs de validation',
                'details' => $errorMessages
            ], 400);
        }

        // Hasher le mot de passe après validation
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Générer un token de vérification d'email
        $verificationToken = Uuid::v4()->toRfc4122();
        $user->setEmailVerificationToken($verificationToken);
        $user->setEmailVerificationExpiresAt(new \DateTimeImmutable('+24 hours'));

        // Sauvegarder l'utilisateur
        $entityManager->persist($user);
        $entityManager->flush();

        // Envoyer l'email de vérification
        try {
            $emailService->sendVerificationEmail($user, $verificationToken);
        } catch (\Exception $e) {
            // En cas d'erreur d'envoi d'email, on continue mais on log l'erreur
            // En production, vous pourriez vouloir gérer cela différemment
        }
        
        return new JsonResponse([
            'message' => 'Utilisateur créé avec succès. Un email de vérification a été envoyé à votre adresse email.',
            'user' => [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'emailVerified' => $user->isEmailVerified()
            ]
        ], 201);
    }

    #[Route('/api/verify-email', name: 'api_verify_email', methods: ['GET'])]
    public function verifyEmail(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $token = $request->query->get('token');

        if (!$token) {
            return new JsonResponse([
                'error' => 'Token de vérification manquant'
            ], 400);
        }

        $user = $entityManager->getRepository(User::class)->findOneBy([
            'emailVerificationToken' => $token
        ]);

        if (!$user) {
            return new JsonResponse([
                'error' => 'Token de vérification invalide'
            ], 400);
        }

        if ($user->isEmailVerified()) {
            return new JsonResponse([
                'message' => 'Votre email est déjà vérifié'
            ], 200);
        }

        if ($user->getEmailVerificationExpiresAt() && $user->getEmailVerificationExpiresAt() < new \DateTimeImmutable()) {
            return new JsonResponse([
                'error' => 'Le token de vérification a expiré'
            ], 400);
        }

        // Marquer l'email comme vérifié
        $user->setEmailVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationExpiresAt(null);

        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Votre email a été vérifié avec succès. Vous pouvez maintenant vous connecter.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'emailVerified' => $user->isEmailVerified()
            ]
        ], 200);
    }

    /**
     * Récupère les informations de l'utilisateur connecté
     * Utilisé par le frontend pour récupérer les données utilisateur après authentification
     */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        // Récupérer l'utilisateur connecté via le token JWT
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'error' => 'Utilisateur non authentifié'
            ], 401);
        }

        // Retourner les informations de l'utilisateur (sans le mot de passe)
        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'emailVerified' => $user->isEmailVerified(),
                'createdAt' => $user->getCreatedAt()?->format('Y-m-d\TH:i:s.v\Z'),
                'updatedAt' => $user->getUpdatedAt()?->format('Y-m-d\TH:i:s.v\Z')
            ]
        ], 200);
    }
} 