<?php

namespace App\Domain\Entity;

enum MissionStatus: string
{
    case InProgress = 'InProgress';

    case Success = 'Success';
    
    case Failure = 'Failure';
} 
