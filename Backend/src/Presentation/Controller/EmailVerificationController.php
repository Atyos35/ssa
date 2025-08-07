<?php

namespace App\Presentation\Controller;

use App\Application\Bus\CommandBusInterface;
use App\Application\Command\ResendVerificationCommand;
use App\Application\Command\VerifyEmailCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmailVerificationController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus
    ) {
    }

    #[Route('/api/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(string $token): JsonResponse
    {
        try {
            $command = new VerifyEmailCommand($token);
            $this->commandBus->dispatch($command);

            return $this->json(['message' => 'Email vérifié avec succès'], Response::HTTP_OK);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/resend-verification', name: 'app_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email requis'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $command = new ResendVerificationCommand($email);
            $this->commandBus->dispatch($command);

            return $this->json(['message' => 'Email de vérification renvoyé'], Response::HTTP_OK);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 
