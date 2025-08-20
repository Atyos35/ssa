<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository personnalisé pour les utilisateurs avec des méthodes de requête optimisées
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Trouve un utilisateur par email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Trouve un utilisateur par token de vérification
     */
    public function findByVerificationToken(string $token): ?User
    {
        return $this->findOneBy(['emailVerificationToken' => $token]);
    }

    /**
     * Trouve un utilisateur par token de refresh
     */
    public function findByRefreshToken(string $token): ?User
    {
        return $this->findOneBy(['refreshToken' => $token]);
    }

    /**
     * Trouve les utilisateurs non vérifiés
     */
    public function findUnverifiedUsers(): array
    {
        return $this->findBy(['emailVerified' => false]);
    }

    /**
     * Trouve les utilisateurs vérifiés
     */
    public function findVerifiedUsers(): array
    {
        return $this->findBy(['emailVerified' => true]);
    }

    /**
     * Trouve les utilisateurs par rôle
     */
    public function findByRole(string $role): array
    {
        return $this->findBy(['roles' => $role]);
    }

    /**
     * Vérifie si un email existe déjà
     */
    public function emailExists(string $email): bool
    {
        return $this->count(['email' => $email]) > 0;
    }

    /**
     * Trouve les utilisateurs avec pagination
     */
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
