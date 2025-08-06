<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationFailureHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class CustomAuthenticationFailureHandler extends AuthenticationFailureHandler
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        // Vérifier si c'est une erreur d'email non vérifié
        if ($exception instanceof CustomUserMessageAuthenticationException) {
            $message = $exception->getMessage();
            
            // Si le message contient "activé" ou "vérifié", c'est une erreur d'email non vérifié
            if (str_contains($message, 'activé') || str_contains($message, 'vérifié')) {
                return new JsonResponse([
                    'code' => 401,
                    'message' => 'Email non vérifié. Veuillez vérifier votre email et cliquer sur le lien de validation avant de vous connecter.'
                ], 401);
            }
        }

        // Pour toutes les autres erreurs d'authentification
        return new JsonResponse([
            'code' => 401,
            'message' => 'Identifiants invalides.'
        ], 401);
    }
} 