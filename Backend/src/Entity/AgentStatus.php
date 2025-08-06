<?php

namespace App\Entity;

/**
 * Statut d'un agent
 */
enum AgentStatus: string
{
    /**
     * Agent actuellement en mission
     */
    case OnMission = 'On Mission';
    /**
     * Agent à la retraite
     */
    case Retired = 'Retired';
    /**
     * Agent tué en mission
     */
    case KilledInAction = 'Killed in Action';
    /**
     * Agent disponible
     */
    case Available = 'Available';
} 