<?php

namespace App\Application\Handler;

use App\Application\Query\QueryInterface;

interface QueryHandlerInterface
{
    public function handle(QueryInterface $query): mixed;
} 