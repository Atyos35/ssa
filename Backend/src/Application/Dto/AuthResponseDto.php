<?php

namespace App\Application\Dto;

class AuthResponseDto
{
    public function __construct(
        public readonly string $message,
        public readonly ?UserDto $user = null
    ) {}
} 