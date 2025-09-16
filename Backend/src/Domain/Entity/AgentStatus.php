<?php

namespace App\Domain\Entity;

enum AgentStatus: string
{
    case OnMission = 'On Mission';
    case Retired = 'Retired';
    case KilledInAction = 'Killed in Action';
    case Available = 'Available';
} 
