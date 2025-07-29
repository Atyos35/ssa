<?php

namespace App\Tests;

use App\Entity\Mission;
use App\Entity\Agent;
use App\Entity\Country;
use PHPUnit\Framework\TestCase;

class MissionTest extends TestCase
{
    public function testAddAgentWithCorrectCountry(): void
    {
        $country = new Country();
        $mission = new Mission();
        $mission->setCountry($country);

        $agent = new Agent();
        $agent->setInfiltratedCountry($country);

        $mission->addAgent($agent);
        $this->assertTrue($mission->getAgents()->contains($agent));
    }

    public function testAddAgentWithWrongCountryThrowsException(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage("L'agent ne peut pas participer à cette mission car il n'est pas infiltré dans le pays de la mission.");

        $country1 = new Country();
        $country2 = new Country();
        $mission = new Mission();
        $mission->setCountry($country1);

        $agent = new Agent();
        $agent->setInfiltratedCountry($country2);

        $mission->addAgent($agent);
    }

    public function testRemoveAgent(): void
    {
        $country = new Country();
        $mission = new Mission();
        $mission->setCountry($country);

        $agent = new Agent();
        $agent->setInfiltratedCountry($country);
        $mission->addAgent($agent);
        $this->assertTrue($mission->getAgents()->contains($agent));

        $mission->removeAgent($agent);
        $this->assertFalse($mission->getAgents()->contains($agent));
    }
}