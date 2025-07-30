<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Obligé de définir ce controlleur, je n'arrive pas à générer une route /login automatiquement avec le firewall
        return new JsonResponse([
            'message' => 'Utilisez POST /login avec email et password dans le body JSON'
        ]);
    }
} 