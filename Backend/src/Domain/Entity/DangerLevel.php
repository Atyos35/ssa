<?php

namespace App\Domain\Entity;

enum DangerLevel: string
{
    case Low = 'Low';
    case Medium = 'Medium';
    case High = 'High';
    case Critical = 'Critical';
} 
