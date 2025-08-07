<?php

namespace App\Presentation\Controller;

use App\Application\Bus\CommandBusInterface;
use App\Application\Bus\QueryBusInterface;
use App\Application\Command\RegisterUserCommand;
use App\Application\Query\GetCurrentUserQuery;
use App\Application\Dto\UserDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Obligé de générer cette route manuellement, je n'arrive pas à la générer automatiquement avec le firewall
        return new JsonResponse([
            'message' => 'Utilisez POST /api/login avec email et password dans le body JSON'
        ]);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Données JSON invalides'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier les champs requis
        $requiredFields = ['firstName', 'lastName', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return $this->json(['error' => "Le champ '$field' est requis"], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            $command = new RegisterUserCommand(
                firstName: $data['firstName'],
                lastName: $data['lastName'],
                email: $data['email'],
                password: $data['password'],
                roles: $data['roles'] ?? ['ROLE_USER']
            );

            $this->commandBus->dispatch($command);

            // Récupérer l'utilisateur créé pour le retourner
            $user = $this->entityManager->getRepository(\App\Domain\Entity\User::class)->findOneBy(['email' => $data['email']]);
            
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé après création');
            }

            $userDto = UserDto::fromEntity($user);

            return $this->json([
                'message' => 'Utilisateur créé avec succès. Un email de vérification a été envoyé à votre adresse email.',
                'user' => $userDto
            ], Response::HTTP_CREATED);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $query = new GetCurrentUserQuery($user->getId());
            $userDto = $this->queryBus->dispatch($query);

            return $this->json(['user' => $userDto], Response::HTTP_OK);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 
