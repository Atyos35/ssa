<?php

namespace App\Domain\Entity;

/**
 * Niveau de danger d'une mission ou d'un pays
 */
enum DangerLevel: string
{
    /**
     * Danger faible
     */
    case Low = 'Low';
    /**
     * Danger moyen
     */
    case Medium = 'Medium';
    /**
     * Danger élevé
     */
    case High = 'High';
    /**
     * Danger critique
     */
    case Critical = 'Critical';
} 
