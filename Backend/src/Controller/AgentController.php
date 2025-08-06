<?php

namespace App\Controller;

use App\Dto\AgentPatchDto;
use App\Entity\Agent;
use App\Service\AgentStatusChangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AgentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly AgentStatusChangeService $statusChangeService
    ) {
    }

    #[Route('/api/agents/{id}/status', name: 'agent_patch_status', methods: ['PATCH'])]
    public function patchStatus(Request $request, string $id): JsonResponse
    {
        // Récupérer l'agent
        $agent = $this->entityManager->getRepository(Agent::class)->find($id);
        if (!$agent) {
            return $this->json(['error' => 'Agent not found'], Response::HTTP_NOT_FOUND);
        }

        // Désérialiser le DTO depuis la requête
        $content = $request->getContent();
        $patchDto = $this->serializer->deserialize($content, AgentPatchDto::class, 'json');

        // Valider le DTO
        $violations = $this->validator->validate($patchDto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $previousStatus = $agent->getStatus();

        // Mettre à jour le statut
        if ($patchDto->status !== null) {
            $agent->setStatus($patchDto->status);
        }

        // Persister les changements
        $this->entityManager->persist($agent);
        $this->entityManager->flush();

        // Traiter le changement de statut
        $this->statusChangeService->handleStatusChange($agent, $previousStatus);

        // Retourner l'agent mis à jour
        return $this->json($agent, Response::HTTP_OK, [], ['groups' => ['agent:read:item']]);
    }
} 