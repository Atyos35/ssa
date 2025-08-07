<?php

namespace App\Application\Command;

class VerifyEmailCommand implements CommandInterface
{
    public function __construct(
        public readonly string $token
    ) {}
} 