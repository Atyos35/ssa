<?php

namespace App\Application\Handler\Query;

use App\Application\Dto\MissionDto;
use App\Application\Handler\QueryHandlerInterface;
use App\Application\Query\GetMissionsQuery;
use App\Application\Query\QueryInterface;
use App\Domain\Entity\Mission;
use App\Infrastructure\Persistence\Repository\MissionRepository;

class GetMissionsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly MissionRepository $missionRepository
    ) {}

    public function handle(QueryInterface $query): array
    {
        if (!$query instanceof GetMissionsQuery) {
            throw new \InvalidArgumentException('Expected GetMissionsQuery');
        }

        $missions = $this->missionRepository->findWithFilters(
            $query->status,
            $query->danger,
            $query->countryId,
            $query->page,
            $query->limit
        );

        return array_map(fn(Mission $mission) => MissionDto::fromEntity($mission), $missions);
    }
} 