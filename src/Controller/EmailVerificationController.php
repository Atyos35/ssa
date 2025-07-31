<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class EmailVerificationController extends AbstractController
{
    #[Route('/api/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(string $token, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->findOneBy([
            'emailVerificationToken' => $token
        ]);

        if (!$user) {
            return $this->json(['error' => 'Token invalide'], 400);
        }

        if ($user->getEmailVerificationExpiresAt() < new \DateTimeImmutable()) {
            return $this->json(['error' => 'Token expiré'], 400);
        }

        if ($user->isEmailVerified()) {
            return $this->json(['message' => 'Email déjà vérifié'], 200);
        }

        // Vérifier l'email
        $user->setEmailVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationExpiresAt(null);
        
        $em->flush();

        return $this->json(['message' => 'Email vérifié avec succès']);
    }

    #[Route('/api/resend-verification', name: 'app_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email requis'], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        if ($user->isEmailVerified()) {
            return $this->json(['error' => 'Email déjà vérifié'], 400);
        }

        // Générer un nouveau token
        $token = Uuid::v4()->toRfc4122();
        $user->setEmailVerificationToken($token);
        $user->setEmailVerificationExpiresAt(new \DateTimeImmutable('+24 hours'));
        
        $em->flush();

        // Envoyer l'email (vous devrez injecter le service EmailVerificationService)
        // $this->emailVerificationService->sendVerificationEmail($user, $token);

        return $this->json(['message' => 'Email de vérification renvoyé']);
    }
} 