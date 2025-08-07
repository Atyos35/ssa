<?php

namespace App\Application\Query;

class GetMissionQuery implements QueryInterface
{
    public function __construct(
        public readonly int $missionId
    ) {}
} 