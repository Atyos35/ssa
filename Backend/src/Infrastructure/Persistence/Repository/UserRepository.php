<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByVerificationToken(string $token): ?User
    {
        return $this->findOneBy(['emailVerificationToken' => $token]);
    }

    public function findByRefreshToken(string $token): ?User
    {
        return $this->findOneBy(['refreshToken' => $token]);
    }

    public function findUnverifiedUsers(): array
    {
        return $this->findBy(['emailVerified' => false]);
    }

    public function findVerifiedUsers(): array
    {
        return $this->findBy(['emailVerified' => true]);
    }

    public function findByRole(string $role): array
    {
        return $this->findBy(['roles' => $role]);
    }

    public function emailExists(string $email): bool
    {
        return $this->count(['email' => $email]) > 0;
    }

    public function findWithPagination(int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;
        
        return $this->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}