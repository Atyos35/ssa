<?php

namespace App\Tests\Unit\Message;

use App\Message\AgentKilledInActionMessage;
use PHPUnit\Framework\TestCase;

class AgentKilledInActionMessageTest extends TestCase
{
    public function testAgentKilledInActionMessageCreation(): void
    {
        $agent = $this->createMock(\App\Domain\Entity\Agent::class);
        $message = new AgentKilledInActionMessage($agent);

        $this->assertSame($agent, $message->getKilledAgent());
    }

    public function testAgentKilledInActionMessageWithDifferentAgent(): void
    {
        $agent1 = $this->createMock(\App\Domain\Entity\Agent::class);
        $agent2 = $this->createMock(\App\Domain\Entity\Agent::class);
        
        $message1 = new AgentKilledInActionMessage($agent1);
        $message2 = new AgentKilledInActionMessage($agent2);

        $this->assertSame($agent1, $message1->getKilledAgent());
        $this->assertSame($agent2, $message2->getKilledAgent());
    }

    public function testAgentKilledInActionMessageIsImmutable(): void
    {
        $agent = $this->createMock(\App\Domain\Entity\Agent::class);
        $message = new AgentKilledInActionMessage($agent);

        // L'agent ne change pas après création
        $this->assertSame($agent, $message->getKilledAgent());
    }
}
