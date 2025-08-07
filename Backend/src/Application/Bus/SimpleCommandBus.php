<?php

namespace App\Application\Bus;

use App\Application\Command\CommandInterface;
use App\Application\Command\CreateMissionCommand;
use App\Application\Command\UpdateMissionCommand;
use App\Application\Command\CreateAgentCommand;
use App\Application\Command\UpdateAgentStatusCommand;
use App\Application\Command\VerifyEmailCommand;
use App\Application\Command\ResendVerificationCommand;
use App\Application\Command\RegisterUserCommand;
use App\Application\Handler\Command\CreateMissionHandler;
use App\Application\Handler\Command\UpdateMissionHandler;
use App\Application\Handler\Command\CreateAgentHandler;
use App\Application\Handler\Command\UpdateAgentStatusHandler;
use App\Application\Handler\Command\VerifyEmailHandler;
use App\Application\Handler\Command\ResendVerificationHandler;
use App\Application\Handler\Command\RegisterUserHandler;

class SimpleCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly CreateMissionHandler $createMissionHandler,
        private readonly UpdateMissionHandler $updateMissionHandler,
        private readonly CreateAgentHandler $createAgentHandler,
        private readonly UpdateAgentStatusHandler $updateAgentStatusHandler,
        private readonly VerifyEmailHandler $verifyEmailHandler,
        private readonly ResendVerificationHandler $resendVerificationHandler,
        private readonly RegisterUserHandler $registerUserHandler
    ) {}

    public function dispatch(CommandInterface $command): void
    {
        match (true) {
            $command instanceof CreateMissionCommand => $this->createMissionHandler->handle($command),
            $command instanceof UpdateMissionCommand => $this->updateMissionHandler->handle($command),
            $command instanceof CreateAgentCommand => $this->createAgentHandler->handle($command),
            $command instanceof UpdateAgentStatusCommand => $this->updateAgentStatusHandler->handle($command),
            $command instanceof VerifyEmailCommand => $this->verifyEmailHandler->handle($command),
            $command instanceof ResendVerificationCommand => $this->resendVerificationHandler->handle($command),
            $command instanceof RegisterUserCommand => $this->registerUserHandler->handle($command),
            default => throw new \InvalidArgumentException('Unknown command: ' . get_class($command))
        };
    }
} 