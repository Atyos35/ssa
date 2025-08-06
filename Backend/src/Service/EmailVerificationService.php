<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class EmailVerificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger
    ) {
    }

    public function sendVerificationEmail(User $user, string $token): void
    {
        $verificationUrl = $this->urlGenerator->generate(
            'api_verify_email',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->from('noreply@ssa-back.local')
            ->to($user->getEmail())
            ->subject('Vérification de votre compte SSA')
            ->html($this->getEmailTemplate($user, $verificationUrl));

        $this->logger->info('Envoi d\'email de vérification', [
            'user_email' => $user->getEmail(),
            'verification_url' => $verificationUrl
        ]);

        $this->mailer->send($email);
        
        $this->logger->info('Email de vérification envoyé avec succès', [
            'user_email' => $user->getEmail()
        ]);
    }

    private function getEmailTemplate(User $user, string $verificationUrl): string
    {
        return "
        <html>
        <body>
            <h2>Bienvenue sur SSA Back !</h2>
            <p>Bonjour {$user->getFirstName()} {$user->getLastName()},</p>
            <p>Merci de vous être inscrit. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
            <p><a href='{$verificationUrl}' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Vérifier mon email</a></p>
            <p>Ou copiez ce lien dans votre navigateur :</p>
            <p>{$verificationUrl}</p>
            <p>Ce lien expirera dans 24 heures.</p>
            <p>Cordialement,<br>L'équipe SSA</p>
        </body>
        </html>
        ";
    }
} 