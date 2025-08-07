<?php

namespace App\Application\Dto;

use App\Domain\Entity\User;

class UserDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly array $roles,
        public readonly bool $emailVerified
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            email: $user->getEmail(),
            roles: $user->getRoles(),
            emailVerified: $user->isEmailVerified()
        );
    }
} 