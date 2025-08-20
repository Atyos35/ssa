<?php

namespace App\Tests\Unit\Application\Bus;

use App\Application\Bus\SimpleCommandBus;
use App\Application\Command\CommandInterface;
use App\Application\Command\CreateMissionCommand;
use App\Application\Handler\Command\CreateMissionHandler;
use App\Application\Handler\Command\UpdateMissionHandler;
use App\Application\Handler\Command\CreateAgentHandler;
use App\Application\Handler\Command\UpdateAgentStatusHandler;
use App\Application\Handler\Command\VerifyEmailHandler;
use App\Application\Handler\Command\ResendVerificationHandler;
use App\Application\Handler\Command\RegisterUserHandler;
use PHPUnit\Framework\TestCase;

class SimpleCommandBusTest extends TestCase
{
    private SimpleCommandBus $commandBus;
    private CreateMissionHandler $createMissionHandler;
    private UpdateMissionHandler $updateMissionHandler;
    private CreateAgentHandler $createAgentHandler;
    private UpdateAgentStatusHandler $updateAgentStatusHandler;
    private VerifyEmailHandler $verifyEmailHandler;
    private ResendVerificationHandler $resendVerificationHandler;
    private RegisterUserHandler $registerUserHandler;

    protected function setUp(): void
    {
        $this->createMissionHandler = $this->createMock(CreateMissionHandler::class);
        $this->updateMissionHandler = $this->createMock(UpdateMissionHandler::class);
        $this->createAgentHandler = $this->createMock(CreateAgentHandler::class);
        $this->updateAgentStatusHandler = $this->createMock(UpdateAgentStatusHandler::class);
        $this->verifyEmailHandler = $this->createMock(VerifyEmailHandler::class);
        $this->resendVerificationHandler = $this->createMock(ResendVerificationHandler::class);
        $this->registerUserHandler = $this->createMock(RegisterUserHandler::class);

        $this->commandBus = new SimpleCommandBus(
            $this->createMissionHandler,
            $this->updateMissionHandler,
            $this->createAgentHandler,
            $this->updateAgentStatusHandler,
            $this->verifyEmailHandler,
            $this->resendVerificationHandler,
            $this->registerUserHandler
        );
    }

    public function testDispatchCreateMissionCommand(): void
    {
        $command = $this->createMock(CreateMissionCommand::class);
        
        $this->createMissionHandler
            ->expects($this->once())
            ->method('handle')
            ->with($command);

        $this->commandBus->dispatch($command);
    }

    public function testDispatchMultipleCommands(): void
    {
        $command1 = $this->createMock(CreateMissionCommand::class);
        $command2 = $this->createMock(CreateMissionCommand::class);
        
        $this->createMissionHandler
            ->expects($this->exactly(2))
            ->method('handle');

        $this->commandBus->dispatch($command1);
        $this->commandBus->dispatch($command2);
    }

    public function testCommandBusIsCommandBusInterface(): void
    {
        $this->assertInstanceOf(\App\Application\Bus\CommandBusInterface::class, $this->commandBus);
    }
}
