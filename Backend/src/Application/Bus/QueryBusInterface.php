<?php

namespace App\Application\Bus;

use App\Application\Query\QueryInterface;

interface QueryBusInterface
{
    public function dispatch(QueryInterface $query): mixed;
} 