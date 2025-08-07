<?php

namespace App\Application\Query;

class GetCurrentUserQuery implements QueryInterface
{
    public function __construct(
        public readonly string $userId
    ) {}
} 