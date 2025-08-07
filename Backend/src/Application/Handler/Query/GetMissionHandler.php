<?php

namespace App\Application\Handler\Query;

use App\Application\Dto\MissionDto;
use App\Application\Handler\QueryHandlerInterface;
use App\Application\Query\GetMissionQuery;
use App\Application\Query\QueryInterface;
use App\Domain\Entity\Mission;
use Doctrine\ORM\EntityManagerInterface;

class GetMissionHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function handle(QueryInterface $query): MissionDto
    {
        if (!$query instanceof GetMissionQuery) {
            throw new \InvalidArgumentException('Expected GetMissionQuery');
        }

        $mission = $this->entityManager->getRepository(Mission::class)->find($query->missionId);
        
        if (!$mission) {
            throw new \DomainException('Mission not found');
        }

        return MissionDto::fromEntity($mission);
    }
} 