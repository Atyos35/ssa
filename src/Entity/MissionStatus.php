<?php

namespace App\Entity;

/**
 * Statut d'une mission
 */
enum MissionStatus: string
{
    /**
     * Mission en cours de planification
     */
    case Planning = 'Planning';
    
    /**
     * Mission en cours
     */
    case InProgress = 'In Progress';
    
    /**
     * Mission en pause
     */
    case Paused = 'Paused';
    
    /**
     * Mission terminée avec succès
     */
    case Success = 'Success';
    
    /**
     * Mission échouée
     */
    case Failure = 'Failure';
    
    /**
     * Mission annulée
     */
    case Cancelled = 'Cancelled';
} 