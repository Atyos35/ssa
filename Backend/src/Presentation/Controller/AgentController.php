<?php

namespace App\Presentation\Controller;

use App\Application\Bus\CommandBusInterface;
use App\Application\Bus\QueryBusInterface;
use App\Application\Command\CreateAgentCommand;
use App\Application\Command\UpdateAgentStatusCommand;
use App\Application\Query\GetAgentQuery;
use App\Application\Query\GetAgentsQuery;
use App\Domain\Entity\AgentStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AgentController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus
    ) {
    }

    #[Route('/api/agents', name: 'agent_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $countryId = $request->query->get('countryId') ? (int) $request->query->get('countryId') : null;
        $page = (int) ($request->query->get('page', 1));
        $limit = (int) ($request->query->get('limit', 10));

        $query = new GetAgentsQuery($status, $countryId, $page, $limit);
        $agents = $this->queryBus->dispatch($query);

        return $this->json($agents, Response::HTTP_OK);
    }

    #[Route('/api/agents/{id}', name: 'agent_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            $query = new GetAgentQuery($id);
            $agent = $this->queryBus->dispatch($query);

            return $this->json($agent, Response::HTTP_OK);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/agents', name: 'agent_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $command = new CreateAgentCommand(
                codeName: $data['codeName'],
                firstName: $data['firstName'],
                lastName: $data['lastName'],
                email: $data['email'],
                password: $data['password'],
                yearsOfExperience: $data['yearsOfExperience'],
                status: AgentStatus::from($data['status'] ?? 'Available'),
                enrolementDate: new \DateTimeImmutable($data['enrolementDate'] ?? 'now'),
                infiltratedCountryId: $data['infiltratedCountryId'] ?? null,
                mentorId: $data['mentorId'] ?? null
            );

            $this->commandBus->dispatch($command);

            return $this->json(['message' => 'Agent created successfully'], Response::HTTP_CREATED);
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid status value'], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/agents/{id}/status', name: 'agent_patch_status', methods: ['PATCH'])]
    public function patchStatus(Request $request, string $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $command = new UpdateAgentStatusCommand(
                agentId: $id,
                status: AgentStatus::from($data['status'])
            );

            $this->commandBus->dispatch($command);

            return $this->json(['status' => $data['status']], Response::HTTP_OK);
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid status value'], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 
