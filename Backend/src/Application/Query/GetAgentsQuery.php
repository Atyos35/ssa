<?php

namespace App\Application\Query;

class GetAgentsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $status = null,
        public readonly ?int $countryId = null,
        public readonly int $page = 1,
        public readonly int $limit = 10
    ) {}
} 