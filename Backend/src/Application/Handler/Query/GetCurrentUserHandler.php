<?php

namespace App\Application\Handler\Query;

use App\Application\Dto\UserDto;
use App\Application\Handler\QueryHandlerInterface;
use App\Application\Query\GetCurrentUserQuery;
use App\Application\Query\QueryInterface;
use App\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class GetCurrentUserHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function handle(QueryInterface $query): UserDto
    {
        if (!$query instanceof GetCurrentUserQuery) {
            throw new \InvalidArgumentException('Expected GetCurrentUserQuery');
        }

        $user = $this->entityManager->getRepository(User::class)->find($query->userId);
        
        if (!$user) {
            throw new \DomainException('Utilisateur non trouvé');
        }

        return UserDto::fromEntity($user);
    }
} 