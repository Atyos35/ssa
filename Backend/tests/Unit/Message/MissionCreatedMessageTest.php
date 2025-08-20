<?php

namespace App\Tests\Unit\Message;

use App\Message\MissionCreatedMessage;
use PHPUnit\Framework\TestCase;

class MissionCreatedMessageTest extends TestCase
{
    public function testMissionCreatedMessageCreation(): void
    {
        $mission = $this->createMock(\App\Domain\Entity\Mission::class);
        $message = new MissionCreatedMessage($mission);

        $this->assertSame($mission, $message->getMission());
    }

    public function testMissionCreatedMessageWithDifferentMission(): void
    {
        $mission1 = $this->createMock(\App\Domain\Entity\Mission::class);
        $mission2 = $this->createMock(\App\Domain\Entity\Mission::class);
        
        $message1 = new MissionCreatedMessage($mission1);
        $message2 = new MissionCreatedMessage($mission2);

        $this->assertSame($mission1, $message1->getMission());
        $this->assertSame($mission2, $message2->getMission());
    }

    public function testMissionCreatedMessageIsImmutable(): void
    {
        $mission = $this->createMock(\App\Domain\Entity\Mission::class);
        $message = new MissionCreatedMessage($mission);

        // La mission ne change pas après création
        $this->assertSame($mission, $message->getMission());
    }

    public function testMissionCreatedMessageReturnsCorrectMission(): void
    {
        $mission = $this->createMock(\App\Domain\Entity\Mission::class);
        $message = new MissionCreatedMessage($mission);

        $retrievedMission = $message->getMission();
        
        $this->assertSame($mission, $retrievedMission);
        $this->assertInstanceOf(\App\Domain\Entity\Mission::class, $retrievedMission);
    }
}
