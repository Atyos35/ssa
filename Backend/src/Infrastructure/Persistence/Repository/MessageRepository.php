<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Message;
use App\Domain\Entity\Agent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository personnalisé pour les messages avec des méthodes de requête optimisées
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Trouve les messages reçus par un agent
     */
    public function findByRecipient(Agent $recipient): array
    {
        return $this->findBy(['recipient' => $recipient]);
    }

    /**
     * Trouve les messages envoyés par un agent
     */
    public function findBySender(Agent $sender): array
    {
        return $this->findBy(['by' => $sender]);
    }

    /**
     * Trouve les messages par destinataire et expéditeur
     */
    public function findByRecipientAndSender(Agent $recipient, Agent $sender): array
    {
        return $this->findBy([
            'recipient' => $recipient,
            'by' => $sender
        ]);
    }

    /**
     * Trouve les messages non lus d'un agent
     */
    public function findUnreadByRecipient(Agent $recipient): array
    {
        return $this->findBy([
            'recipient' => $recipient,
            'isRead' => false
        ]);
    }

    /**
     * Trouve les messages par titre
     */
    public function findByTitle(string $title): array
    {
        return $this->findBy(['title' => $title]);
    }

    /**
     * Trouve les messages contenant un texte dans le corps
     */
    public function findByBodyContaining(string $text): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.body LIKE :text')
            ->setParameter('text', '%' . $text . '%');

        return $qb->getQuery()->getResult();
    }

    /**
     * Supprime tous les messages d'un agent
     */
    public function deleteAllByAgent(Agent $agent): int
    {
        $qb = $this->createQueryBuilder('m')
            ->delete()
            ->where('m.recipient = :agent OR m.by = :agent')
            ->setParameter('agent', $agent);

        return $qb->getQuery()->execute();
    }

    /**
     * Trouve les messages avec pagination
     */
    public function findWithPagination(int $page, int $limit, ?Agent $recipient = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'DESC');

        if ($recipient !== null) {
            $qb->andWhere('m.recipient = :recipient')
               ->setParameter('recipient', $recipient);
        }

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}

