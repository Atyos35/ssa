<?php

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Entity\Message;
use App\Domain\Entity\Agent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findByRecipient(Agent $recipient): array
    {
        return $this->findBy(['recipient' => $recipient]);
    }

    public function findBySender(Agent $sender): array
    {
        return $this->findBy(['by' => $sender]);
    }

    public function findByRecipientAndSender(Agent $recipient, Agent $sender): array
    {
        return $this->findBy([
            'recipient' => $recipient,
            'by' => $sender
        ]);
    }

    public function findUnreadByRecipient(Agent $recipient): array
    {
        return $this->findBy([
            'recipient' => $recipient,
            'isRead' => false
        ]);
    }

    public function findByTitle(string $title): array
    {
        return $this->findBy(['title' => $title]);
    }

    public function findByBodyContaining(string $text): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.body LIKE :text')
            ->setParameter('text', '%' . $text . '%');

        return $qb->getQuery()->getResult();
    }

    public function deleteAllByAgent(Agent $agent): int
    {
        $qb = $this->createQueryBuilder('m')
            ->delete()
            ->where('m.recipient = :agent OR m.by = :agent')
            ->setParameter('agent', $agent);

        return $qb->getQuery()->execute();
    }

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