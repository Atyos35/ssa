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
    public function verifyEmail(string $token): Response
    {
        try {
            $command = new VerifyEmailCommand($token);
            $this->commandBus->dispatch($command);

            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Email vérifié</title>
            </head>
            <body>
                <div class="container">
                    <div class="success">Email vérifié avec succès !</div>
                    <p>Votre adresse email a été validée. Vous pouvez maintenant vous connecter à votre compte.</p>
                    <a href="http://localhost:3000/login" class="btn">Aller à la page de connexion</a>
                </div>
            </body>
            </html>';

            return new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html']);

        } catch (\DomainException $e) {
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Erreur de validation</title>
            </head>
            <body>
                <div class="container">
                    <div class="error">Erreur de validation</div>
                    <p>' . htmlspecialchars($e->getMessage()) . '</p>
                    <a href="http://localhost:3000/registration" class="btn">Retourner à l\'inscription</a>
                </div>
            </body>
            </html>';

            return new Response($html, Response::HTTP_BAD_REQUEST, ['Content-Type' => 'text/html']);

        } catch (\Exception $e) {
            $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Erreur serveur</title>
            </head>
            <body>
                <div class="container">
                    <div class="error">Erreur serveur</div>
                    <p>Une erreur interne est survenue. Veuillez réessayer plus tard.</p>
                    <a href="http://localhost:3000/registration" class="btn">Retourner à l\'inscription</a>
                </div>
            </body>
            </html>';

            return new Response($html, Response::HTTP_INTERNAL_SERVER_ERROR, ['Content-Type' => 'text/html']);
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
