<?php

namespace App\Application\Handler;

use App\Application\Command\CommandInterface;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): void;
} 