<?php

namespace App\Controller;

use App\Entity\Mission;
use App\Service\MissionCreationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MissionController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly MissionCreationService $missionCreationService
    ) {
    }

    #[Route('/api/missions', name: 'mission_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // Désérialiser la mission depuis la requête
        $content = $request->getContent();
        $mission = $this->serializer->deserialize($content, Mission::class, 'json');

        // Valider la mission
        $violations = $this->validator->validate($mission);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        // Utiliser le service pour créer la mission et envoyer les notifications
        $this->missionCreationService->handleMissionCreation($mission);

        // Retourner la mission créée
        return $this->json($mission, Response::HTTP_CREATED, [], ['groups' => ['mission:read:item']]);
    }
} 