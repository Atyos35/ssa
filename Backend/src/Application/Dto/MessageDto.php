<?php

namespace App\Application\Dto;

use App\Domain\Entity\Message;

class MessageDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $body,
        public readonly ?array $by,
        public readonly ?array $recipient
    ) {}

    public static function fromEntity(Message $message): self
    {
        return new self(
            id: $message->getId(),
            title: $message->getTitle(),
            body: $message->getBody(),
            by: null, // On ne retourne pas l'expéditeur pour des raisons de sécurité
            recipient: null // On ne retourne pas le destinataire pour des raisons de sécurité
        );
    }
} 