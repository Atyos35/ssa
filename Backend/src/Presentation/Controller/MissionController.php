<?php

namespace App\Presentation\Controller;

use App\Application\Bus\CommandBusInterface;
use App\Application\Bus\QueryBusInterface;
use App\Application\Command\CreateMissionCommand;
use App\Application\Command\UpdateMissionCommand;
use App\Application\Query\GetMissionQuery;
use App\Application\Query\GetMissionsQuery;
use App\Domain\Entity\DangerLevel;
use App\Domain\Entity\MissionStatus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MissionController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus
    ) {
    }

    #[Route('/api/missions', name: 'mission_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $danger = $request->query->get('danger');
        $countryId = $request->query->get('countryId') ? (int) $request->query->get('countryId') : null;
        $page = (int) ($request->query->get('page', 1));
        $limit = (int) ($request->query->get('limit', 10));

        $query = new GetMissionsQuery($status, $danger, $countryId, $page, $limit);
        $missions = $this->queryBus->dispatch($query);

        return $this->json($missions, Response::HTTP_OK);
    }

    #[Route('/api/missions/{id}', name: 'mission_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        try {
            $query = new GetMissionQuery((int) $id);
            $mission = $this->queryBus->dispatch($query);

            return $this->json($mission, Response::HTTP_OK);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/missions', name: 'mission_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            // Extraire les IDs des références API Platform
            $countryId = null;
            if (isset($data['country'])) {
                if (is_string($data['country']) && strpos($data['country'], '/api/countries/') === 0) {
                    $countryId = (int) basename($data['country']);
                } else {
                    $countryId = (int) $data['country'];
                }
            } elseif (isset($data['countryId'])) {
                $countryId = (int) $data['countryId'];
            }

            $agentIds = [];
            if (isset($data['agents'])) {
                foreach ($data['agents'] as $agentRef) {
                    if (is_string($agentRef) && strpos($agentRef, '/api/agents/') === 0) {
                        $agentIds[] = basename($agentRef);
                    } else {
                        $agentIds[] = $agentRef;
                    }
                }
            } elseif (isset($data['agentIds'])) {
                $agentIds = $data['agentIds'];
            }

            $command = new CreateMissionCommand(
                name: $data['name'],
                description: $data['description'],
                objectives: $data['objectives'],
                danger: DangerLevel::from($data['danger']),
                status: MissionStatus::from($data['status']),
                startDate: new \DateTimeImmutable($data['startDate']),
                endDate: isset($data['endDate']) ? new \DateTimeImmutable($data['endDate']) : null,
                countryId: $countryId,
                agentIds: $agentIds
            );

            $this->commandBus->dispatch($command);

            return $this->json(['message' => 'Mission created successfully'], Response::HTTP_CREATED);
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid enum value provided'], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/missions/{id}', name: 'mission_patch', methods: ['PATCH'])]
    public function patch(Request $request, string $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            // Extraire les IDs des références API Platform
            $countryId = null;
            if (isset($data['country'])) {
                if (is_string($data['country']) && strpos($data['country'], '/api/countries/') === 0) {
                    $countryId = (int) basename($data['country']);
                } else {
                    $countryId = (int) $data['country'];
                }
            } elseif (isset($data['countryId'])) {
                $countryId = (int) $data['countryId'];
            }

            $agentIds = null;
            if (isset($data['agents'])) {
                $agentIds = [];
                foreach ($data['agents'] as $agentRef) {
                    if (is_string($agentRef) && strpos($agentRef, '/api/agents/') === 0) {
                        $agentIds[] = basename($agentRef);
                    } else {
                        $agentIds[] = $agentRef;
                    }
                }
            } elseif (isset($data['agentIds'])) {
                $agentIds = $data['agentIds'];
            }

            $command = new UpdateMissionCommand(
                missionId: (int) $id,
                name: $data['name'] ?? null,
                description: $data['description'] ?? null,
                objectives: $data['objectives'] ?? null,
                danger: isset($data['danger']) ? DangerLevel::from($data['danger']) : null,
                status: isset($data['status']) ? MissionStatus::from($data['status']) : null,
                startDate: isset($data['startDate']) ? new \DateTimeImmutable($data['startDate']) : null,
                endDate: isset($data['endDate']) ? new \DateTimeImmutable($data['endDate']) : null,
                countryId: $countryId,
                agentIds: $agentIds,
                missionResultSummary: $data['missionResultSummary'] ?? null
            );

            $this->commandBus->dispatch($command);

            return $this->json(['message' => 'Mission updated successfully'], Response::HTTP_OK);
        } catch (\ValueError $e) {
            return $this->json(['error' => 'Invalid enum value provided'], Response::HTTP_BAD_REQUEST);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
} 
