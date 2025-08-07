<?php

namespace App\Domain\Entity;

/**
 * Statut d'une mission
 */
enum MissionStatus: string
{
    /**
     * Mission en cours
     */
    case InProgress = 'InProgress';

    /**
     * Mission terminée avec succès
     */
    case Success = 'Success';
    
    /**
     * Mission échouée
     */
    case Failure = 'Failure';
} 
