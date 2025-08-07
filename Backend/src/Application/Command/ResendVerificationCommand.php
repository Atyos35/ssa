<?php

namespace App\Application\Command;

class ResendVerificationCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email
    ) {}
} 