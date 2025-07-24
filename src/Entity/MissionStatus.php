<?php

namespace App\Entity;

/**
 * Résultat de mission
 */
enum MissionStatus: string
{
    /**
     * Mission réussie
     */
    case Success = 'Success';
    /**
     * Mission échouée
     */
    case Failure = 'Failure';
} 